<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Políticas de Uso y Condiciones de la Plataforma - AutoBitacora</title>

    <!-- Favicon para navegadores web -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/autobitacoraLogo.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/autobitacoraLogo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/autobitacoraLogo.png') }}">

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        body {
            background-color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 0;
            font-size: 0.9rem;
        }

        .policy-modal-card {
            width: 100%;
            max-width: 900px;
            border-radius: 0.75rem;
            background: #ffffff;
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }

        .policy-scroll-box {
            max-height: 52vh;
            overflow-y: scroll;
            padding: 1.5rem;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            line-height: 1.6;
        }

        .policy-scroll-box h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1e293b;
            margin-top: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .scroll-indicator {
            font-size: 0.75rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center">
        <div class="card policy-modal-card border-0">
            {{-- Encabezado Modal Bloqueante --}}
            <div class="card-header bg-dark text-white py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <img src="{{ asset('images/autobitacoraLogo.png') }}" alt="AutoBitacora" height="32" class="me-2 rounded bg-white p-1">
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $policy->title }}</h5>
                        <small class="text-secondary">AutoBitacora SaaS &bull; Versión {{ $policy->version }} &bull; Vigencia: {{ $policy->effective_date?->format('d/m/Y') }}</small>
                    </div>
                </div>
                <span class="badge bg-warning text-dark px-2 py-1">Lectura Obligatoria</span>
            </div>

            <div class="card-body p-4">
                <div class="alert alert-info py-2 px-3 small mb-3 d-flex align-items-center">
                    <span class="fs-5 me-2">&#9432;</span>
                    <div>
                        Hola, <strong>{{ $user->name }}</strong>. Para continuar utilizando la plataforma, debes leer el documento completo hasta el final y confirmar tu aceptación.
                    </div>
                </div>

                {{-- Contenedor de Lectura con Scroll --}}
                <div class="policy-scroll-box mb-3" id="policyScrollBox">
                    <div class="text-secondary">
                        {!! Str::markdown($policy->content) !!}
                    </div>
                    <div class="p-3 mt-4 bg-white border rounded text-center small text-muted" id="endOfDocument">
                        &#10003; Has llegado al final del documento de Políticas y Condiciones de la Plataforma.
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="scroll-indicator" id="scrollStatusNotice">
                        &#8595; Desplázate hasta el final para habilitar la casilla de aceptación.
                    </span>
                    <span class="badge bg-light text-dark border small" id="scrollPercentBadge">0% leído</span>
                </div>

                <form method="POST" action="{{ route('policy.accept') }}" id="policyForm">
                    @csrf
                    <input type="hidden" name="policy_id" value="{{ $policy->id }}">

                    <div class="form-check p-3 border rounded bg-light mb-3">
                        <input 
                            class="form-check-input ms-0 me-2" 
                            type="checkbox" 
                            name="accepted" 
                            value="1" 
                            id="acceptCheckbox" 
                            disabled
                        >
                        <label class="form-check-label fw-bold text-dark" for="acceptCheckbox">
                            He leído y acepto las Políticas de Uso y Condiciones de la Plataforma (Versión {{ $policy->version }})
                        </label>
                        @error('accepted')
                            <div class="text-danger small mt-1 d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        {{-- Botón para Cerrar Sesión si no desea aceptar --}}
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="document.getElementById('logoutFormPolicy').submit();">
                            &#8617; No Acepto (Cerrar Sesión)
                        </button>

                        {{-- Botón Principal Bloqueado hasta checkear --}}
                        <button type="submit" class="btn btn-primary px-4 fw-bold" id="btnSubmitPolicy" disabled>
                            Aceptar y Continuar &rarr;
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('logout') }}" id="logoutFormPolicy" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div>

    <!-- Script de Control: Scroll al Final + Checkbox + Habilitación de Botón -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const scrollBox = document.getElementById('policyScrollBox');
            const checkbox = document.getElementById('acceptCheckbox');
            const btnSubmit = document.getElementById('btnSubmitPolicy');
            const statusNotice = document.getElementById('scrollStatusNotice');
            const percentBadge = document.getElementById('scrollPercentBadge');

            let hasReachedBottom = false;

            scrollBox.addEventListener('scroll', function () {
                const scrollTop = scrollBox.scrollTop;
                const scrollHeight = scrollBox.scrollHeight;
                const clientHeight = scrollBox.clientHeight;

                const percent = Math.min(100, Math.round(((scrollTop + clientHeight) / scrollHeight) * 100));
                percentBadge.textContent = percent + '% leído';

                // Tolerancia de 15px para considerar que llegó al final
                if ((scrollTop + clientHeight >= scrollHeight - 20) && !hasReachedBottom) {
                    hasReachedBottom = true;
                    checkbox.disabled = false;
                    checkbox.classList.add('border-primary');
                    statusNotice.innerHTML = '<span class="text-success fw-bold">&#10003; Documento completado. Ya puedes marcar la casilla para continuar.</span>';
                }
            });

            checkbox.addEventListener('change', function () {
                btnSubmit.disabled = !this.checked;
            });
        });
    </script>
</body>
</html>
