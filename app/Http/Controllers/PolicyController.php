<?php

namespace App\Http\Controllers;

use App\Domain\Users\Models\PlatformPolicy;
use App\Domain\Users\Models\UserPolicyAcceptance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PolicyController extends Controller
{
    /**
     * Muestra la pantalla modal/bloqueante con las políticas para su lectura y aceptación obligatoria.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $policy = PlatformPolicy::activePolicy();

        if (! $policy) {
            return redirect()->route('dashboard');
        }

        if ($user->hasAcceptedCurrentPolicy()) {
            return redirect()->route('dashboard');
        }

        return view('policies.show', compact('policy', 'user'));
    }

    /**
     * Registra la evidencia legal de aceptación y desbloquea el acceso al sistema.
     */
    public function accept(Request $request): RedirectResponse
    {
        $request->validate([
            'accepted' => ['required', 'accepted'],
            'policy_id' => ['required', 'exists:platform_policies,id'],
        ]);

        $user = $request->user();
        $policy = PlatformPolicy::findOrFail($request->input('policy_id'));

        UserPolicyAcceptance::updateOrCreate(
            [
                'user_id' => $user->id,
                'platform_policy_id' => $policy->id,
            ],
            [
                'policy_version' => $policy->version,
                'accepted_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        return redirect()
            ->route('dashboard')
            ->with('success', 'Has aceptado las Políticas de Uso y Condiciones de la Plataforma (v' . $policy->version . '). Bienvenido a AutoBitacora.');
    }
}
