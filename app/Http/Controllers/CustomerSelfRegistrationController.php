<?php

namespace App\Http\Controllers;

use App\Domain\Companies\Models\Customer;
use App\Models\User;
use App\Rules\TurnstileRule;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerSelfRegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.customer-register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email', Rule::unique('customers', 'email')],
            'phone' => ['nullable', 'string', 'max:50'],
            'customer_type' => ['required', Rule::in(['individual', 'fleet_owner'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'cf-turnstile-response' => [new TurnstileRule],
        ]);

        $user = DB::transaction(function () use ($data): User {
            $customer = Customer::create([
                'company_id' => null,
                'full_name' => $data['full_name'],
                'email' => mb_strtolower($data['email']),
                'phone' => $data['phone'] ?? null,
                'customer_type' => $data['customer_type'],
                'fleet_trial_started_at' => now(),
            ]);

            $emailName = mb_strstr($data['email'], '@', true);
            $baseUsername = Str::slug($emailName ?: $data['full_name'], '_') ?: 'cliente';
            $username = $baseUsername;
            $suffix = 1;
            while (User::query()->where('username', $username)->exists()) {
                $username = $baseUsername.'_'.$suffix;
                $suffix++;
            }

            return User::create([
                'name' => $data['full_name'],
                'username' => $username,
                'email' => mb_strtolower($data['email']),
                'password' => $data['password'],
                'role' => 'customer',
                'company_id' => null,
                'customer_id' => $customer->id,
                'is_active' => true,
                'account_status' => 'ACTIVE',
                'activated_at' => now(),
                'email_verification_required' => true,
            ]);
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('status', 'Te enviamos un correo para verificar tu dirección antes de usar el portal.');
    }
}
