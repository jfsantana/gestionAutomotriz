<?php

namespace App\Http\Controllers;

use App\Domain\Companies\Models\Customer;
use App\Domain\Companies\Models\WorkshopAssociationInvitation;
use App\Services\CustomerWorkshopRelationshipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkshopCustomerRelationshipController extends Controller
{
    public function create(): View
    {
        return view('workshop_associations.create');
    }

    public function store(Request $request, CustomerWorkshopRelationshipService $relationships): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);
        $company = $request->user()->company;
        abort_unless($company, 403);

        $invitationSent = $relationships->inviteOrphanCustomer(
            mb_strtolower($data['email']),
            $company,
            $request->user(),
        );

        if ($invitationSent === false) {
            return back()->with('error', 'No se pudo enviar la invitación. Verifica la configuración de correo e inténtalo nuevamente.');
        }

        return back()->with(
            'success',
            'Si existe una cuenta de cliente huérfano asociada a ese correo, recibirá una invitación válida por 48 horas para aceptar la asociación.'
        );
    }

    public function showInvitation(string $token, Request $request): View
    {
        $invitation = WorkshopAssociationInvitation::query()
            ->with(['customer', 'company'])
            ->where('token', hash('sha256', $token))
            ->firstOrFail();

        abort_unless($invitation->isValid(), 410, 'La invitación venció o ya fue utilizada.');
        abort_unless(
            $request->user()->role === 'customer'
            && (int) $request->user()->customer_id === (int) $invitation->customer_id,
            403,
            'Esta invitación no corresponde a tu cuenta.'
        );

        return view('workshop_associations.show', compact('invitation', 'token'));
    }

    public function acceptInvitation(
        string $token,
        Request $request,
        CustomerWorkshopRelationshipService $relationships,
    ): RedirectResponse {
        $customer = $relationships->acceptInvitation($token, $request->user());

        return redirect()->route('portal.account')
            ->with('success', "Aceptaste la invitación. Ahora estás asociado a {$customer->company?->name}.");
    }

    public function endForCustomer(
        Customer $customer,
        Request $request,
        CustomerWorkshopRelationshipService $relationships,
    ): RedirectResponse {
        abort_unless(
            $request->user()->role === 'super_admin'
            || (int) $customer->company_id === (int) $request->user()->company_id,
            404
        );

        $emailSent = $relationships->endRelationship($customer, $request->user());

        return redirect()->route('customers.index')->with(
            $emailSent ? 'success' : 'warning',
            $emailSent
                ? 'La relación con el cliente se cerró. Se conservaron los vehículos y el historial; los precios históricos fueron eliminados y se notificó al cliente.'
                : 'La relación se cerró y se conservaron los vehículos e historial sin precios, pero no se pudo enviar el aviso por correo. Revisa la configuración de correo.'
        );
    }

    public function endForWorkshop(
        Request $request,
        CustomerWorkshopRelationshipService $relationships,
    ): RedirectResponse {
        $customer = $request->user()->customer;
        abort_unless($customer, 404);

        $emailSent = $relationships->endRelationship($customer, $request->user());

        return redirect()->route('portal.account')->with(
            $emailSent ? 'success' : 'warning',
            $emailSent
                ? 'Tu relación con el taller se cerró. Conservas tus vehículos y tu historial; los precios históricos fueron eliminados. Se notificó al taller.'
                : 'La relación se cerró y tus datos se conservaron sin precios, pero no se pudo enviar el aviso al taller. Contacta a AutoBitácora.'
        );
    }
}
