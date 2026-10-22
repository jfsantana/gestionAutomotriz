<?php

namespace App\Http\Controllers;

use App\Domain\Companies\Models\Customer;
use App\Domain\Users\Models\ClientInvitation;
use App\Mail\ClientPortalInvitationMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CustomerInvitationController extends Controller
{
    /**
     * Envía o reenvía la invitación al portal del cliente por correo electrónico.
     */
    public function send(Request $request, Customer $customer): RedirectResponse
    {
        $currentUser = $request->user();

        if ($currentUser->role === 'company_admin' && (int)$customer->company_id !== (int)$currentUser->company_id) {
            abort(403, 'No tienes autorización sobre los clientes de otra empresa.');
        }

        if (empty($customer->email)) {
            return back()->with('error', 'El cliente no tiene un correo electrónico registrado. Por favor, edita sus datos y agrega un email válido antes de invitarlo.');
        }

        $company = $customer->company;

        // 1. Obtener o crear el usuario con rol customer
        $user = User::where('customer_id', $customer->id)
            ->orWhere('email', $customer->email)
            ->first();

        if (! $user) {
            $baseUsername = Str::slug($customer->full_name);
            $username = $baseUsername ?: 'cliente';
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = "{$baseUsername}_{$counter}";
                $counter++;
            }

            $user = User::create([
                'name' => $customer->full_name,
                'username' => $username,
                'email' => $customer->email,
                'role' => 'customer',
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
                'is_active' => false,
                'account_status' => 'INVITED',
                'password' => Hash::make(Str::random(32)),
            ]);
        } else {
            // Asegurar asignación al customer
            $user->update([
                'customer_id' => $customer->id,
                'company_id' => $customer->company_id,
                'role' => 'customer',
            ]);
        }

        // 2. Invalidar invitaciones previas sin usar
        ClientInvitation::where('customer_id', $customer->id)
            ->whereNull('used_at')
            ->delete();

        // 3. Generar token único válido por 48 horas
        $plainToken = Str::random(48);
        $hashedToken = hash('sha256', $plainToken);

        ClientInvitation::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'company_id' => $customer->company_id,
            'token' => $hashedToken,
            'expires_at' => now()->addHours(48),
            'created_by' => $currentUser->id,
        ]);

        // 4. Enviar correo SMTP
        Mail::to($customer->email)->send(new ClientPortalInvitationMail($customer, $company, $plainToken));

        return back()->with('success', "Invitación enviada exitosamente a {$customer->email}. El cliente tiene 48 horas para activar su cuenta.");
    }
}
