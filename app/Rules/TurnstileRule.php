<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class TurnstileRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secretKey = config('services.turnstile.secret');

        if (empty($secretKey)) {
            return;
        }

        if (empty($value)) {
            $fail('Por favor complete la verificación de seguridad (Captcha).');
            return;
        }

        try {
            $response = Http::asForm()
                ->withoutVerifying()
                ->timeout(10)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secretKey,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);

            if (! $response->successful() || ! ($response->json('success') === true)) {
                \Illuminate\Support\Facades\Log::warning('Turnstile falló verificación:', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                $errorCodes = $response->json('error-codes', []);
                $fail('La verificación de Cloudflare ha fallado (' . implode(', ', $errorCodes) . '). Intente nuevamente.');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Excepción al conectar con Cloudflare Turnstile: ' . $e->getMessage());
            $fail('Error al conectar con el servicio de verificación: ' . $e->getMessage());
        }
    }
}
