<?php

namespace App\Services;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Companies\Models\CustomerWorkshopRelationship;
use App\Domain\Companies\Models\WorkshopAssociationInvitation;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Users\Models\ClientInvitation;
use App\Domain\Vehicles\Models\Vehicle;
use App\Mail\CustomerWorkshopRelationshipEndedMail;
use App\Mail\WorkshopAssociationInvitationMail;
use App\Mail\WorkshopCustomerRelationshipEndedMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class CustomerWorkshopRelationshipService
{
    public function inviteOrphanCustomer(string $email, Company $company, User $inviter): ?bool
    {
        $customers = Customer::query()
            ->whereNull('company_id')
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->with('user')
            ->limit(2)
            ->get();

        if ($customers->count() !== 1 || ! $customers->first()->user?->is_active) {
            return null;
        }

        $customer = $customers->first();
        $user = $customer->user;
        if ($user->role !== 'customer') {
            return null;
        }

        WorkshopAssociationInvitation::query()
            ->where('customer_id', $customer->id)
            ->where('company_id', $company->id)
            ->whereNull('accepted_at')
            ->delete();

        $plainToken = Str::random(48);
        $invitation = WorkshopAssociationInvitation::create([
            'customer_id' => $customer->id,
            'company_id' => $company->id,
            'invited_by_user_id' => $inviter->id,
            'token' => hash('sha256', $plainToken),
            'expires_at' => now()->addHours(48),
        ]);

        try {
            Mail::to($user->email)->send(new WorkshopAssociationInvitationMail($invitation, $plainToken));
        } catch (TransportExceptionInterface $exception) {
            Log::error('La invitación de asociación quedó registrada, pero no se pudo enviar el correo.', [
                'invitation_id' => $invitation->id,
                'customer_id' => $customer->id,
                'company_id' => $company->id,
                'exception_class' => $exception::class,
            ]);

            return false;
        }

        return true;
    }

    public function acceptInvitation(string $token, User $user): Customer
    {
        return DB::transaction(function () use ($token, $user): Customer {
            $invitation = WorkshopAssociationInvitation::query()
                ->where('token', hash('sha256', $token))
                ->lockForUpdate()
                ->firstOrFail();

            if (! $invitation->isValid()) {
                throw ValidationException::withMessages([
                    'invitation' => 'La invitación venció o ya fue utilizada. Solicita una nueva al taller.',
                ]);
            }

            $customer = Customer::query()->lockForUpdate()->findOrFail($invitation->customer_id);
            $company = Company::query()->findOrFail($invitation->company_id);

            if ((int) $user->customer_id !== (int) $customer->id
                || $user->role !== 'customer'
                || (int) $user->id !== (int) $customer->user?->id) {
                abort(403, 'Esta invitación no corresponde a tu cuenta.');
            }

            if ($customer->company_id !== null) {
                throw ValidationException::withMessages([
                    'invitation' => 'El cliente ya está asociado a un taller. Solo puede tener una relación activa.',
                ]);
            }

            if ($company->status !== 'active' || ! $company->hasActiveSubscription()) {
                throw ValidationException::withMessages([
                    'invitation' => 'El taller no está disponible para aceptar nuevas asociaciones en este momento.',
                ]);
            }

            $vehicles = Vehicle::withTrashed()
                ->where('customer_id', $customer->id)
                ->lockForUpdate()
                ->get(['id', 'plate']);

            if (Vehicle::withTrashed()
                ->where('company_id', $company->id)
                ->where('customer_id', '<>', $customer->id)
                ->whereIn('plate', $vehicles->pluck('plate'))
                ->exists()) {
                throw ValidationException::withMessages([
                    'invitation' => 'No se pudo completar la asociación porque una placa ya está registrada en el taller. Contacta al administrador.',
                ]);
            }

            if ($vehicles->pluck('plate')->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'invitation' => 'No se pudo completar la asociación porque hay placas repetidas entre los vehículos del cliente.',
                ]);
            }

            $customer->update(['company_id' => $company->id]);
            Vehicle::withTrashed()->where('customer_id', $customer->id)->update(['company_id' => $company->id]);
            User::query()->where('customer_id', $customer->id)->where('role', 'customer')
                ->update(['company_id' => $company->id]);

            ClientInvitation::query()
                ->where('customer_id', $customer->id)
                ->whereNull('used_at')
                ->delete();

            CustomerWorkshopRelationship::create([
                'customer_id' => $customer->id,
                'company_id' => $company->id,
                'started_at' => now(),
            ]);

            WorkshopAssociationInvitation::query()
                ->where('customer_id', $customer->id)
                ->whereNull('accepted_at')
                ->whereKeyNot($invitation->id)
                ->delete();

            $invitation->update(['accepted_at' => now()]);

            return $customer->refresh();
        });
    }

    public function endRelationship(Customer $customer, User $actor): bool
    {
        $company = DB::transaction(function () use ($customer, $actor): Company {
            $lockedCustomer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $companyId = $lockedCustomer->company_id;

            if ($companyId === null) {
                throw ValidationException::withMessages([
                    'relationship' => 'El cliente ya no tiene un taller asociado.',
                ]);
            }

            if ($actor->role === 'customer') {
                if ((int) $actor->customer_id !== (int) $lockedCustomer->id) {
                    abort(403);
                }

                if (! $lockedCustomer->isFleetPremiumActive()) {
                    throw ValidationException::withMessages([
                        'relationship' => 'Solo un cliente con suscripción de flota pagada y vigente puede cerrar su relación con el taller.',
                    ]);
                }
            } elseif ($actor->role === 'company_admin') {
                if ((int) $actor->company_id !== (int) $companyId) {
                    abort(403);
                }
            } elseif ($actor->role !== 'super_admin') {
                abort(403);
            }

            $company = Company::query()->findOrFail($companyId);
            $activeRelationships = CustomerWorkshopRelationship::query()
                ->where('customer_id', $lockedCustomer->id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->get();

            if ($activeRelationships->isEmpty()) {
                $activeRelationships = collect([CustomerWorkshopRelationship::create([
                    'customer_id' => $lockedCustomer->id,
                    'company_id' => $companyId,
                    'started_at' => $lockedCustomer->created_at ?? now(),
                ])]);
            }

            foreach ($activeRelationships as $relationship) {
                $relationship->update([
                    'ended_at' => now(),
                    'ended_by_user_id' => $actor->id,
                    'ended_by_role' => $actor->role,
                ]);
            }

            $lockedCustomer->update(['company_id' => null]);
            Vehicle::withTrashed()->where('customer_id', $lockedCustomer->id)->update(['company_id' => null]);
            User::query()->where('customer_id', $lockedCustomer->id)->where('role', 'customer')
                ->update(['company_id' => null]);
            MaintenanceOrder::withTrashed()
                ->where('customer_id', $lockedCustomer->id)
                ->update(['cost' => null]);
            ClientInvitation::query()
                ->where('customer_id', $lockedCustomer->id)
                ->whereNull('used_at')
                ->delete();
            WorkshopAssociationInvitation::query()
                ->where('customer_id', $lockedCustomer->id)
                ->whereNull('accepted_at')
                ->delete();

            return $company;
        });

        $customer->refresh();

        return $this->sendRelationshipEndedEmail($customer, $company, $actor);
    }

    private function sendRelationshipEndedEmail(Customer $customer, Company $company, User $actor): bool
    {
        if ($actor->role === 'customer') {
            $recipients = $company->admins()
                ->where('is_active', true)
                ->whereNotNull('email')
                ->pluck('email')
                ->all();

            if ($recipients === [] && $company->email) {
                $recipients = [$company->email];
            }

            if ($recipients === []) {
                Log::error('La relación se cerró, pero el taller no tiene destinatarios con email para recibir el aviso.', [
                    'customer_id' => $customer->id,
                    'company_id' => $company->id,
                ]);

                return false;
            }

            $mail = new WorkshopCustomerRelationshipEndedMail($customer, $company);
        } else {
            $recipient = $customer->user?->email ?: $customer->email;
            if (! $recipient) {
                Log::error('La relación se cerró, pero el cliente no tiene email para recibir el aviso.', [
                    'customer_id' => $customer->id,
                    'company_id' => $company->id,
                ]);

                return false;
            }

            $recipients = [$recipient];
            $mail = new CustomerWorkshopRelationshipEndedMail($customer, $company);
        }

        try {
            Mail::to($recipients)->send($mail);
        } catch (TransportExceptionInterface $exception) {
            Log::error('La relación se cerró, pero no se pudo enviar el correo de notificación.', [
                'customer_id' => $customer->id,
                'company_id' => $company->id,
                'exception_class' => $exception::class,
            ]);

            return false;
        }

        return true;
    }
}
