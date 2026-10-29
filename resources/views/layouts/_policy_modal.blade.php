{{-- MODAL INFORMATIVO PARA CONSULTAR POLÍTICAS ACEPTADAS EN CUALQUIER MOMENTO --}}
@if(isset($currentPlatformPolicy) && $currentPlatformPolicy)
    <div class="modal fade" id="viewAcceptedPolicyModal" tabindex="-1" aria-labelledby="viewAcceptedPolicyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-dark text-white py-3">
                    <div class="d-flex align-items-center">
                        <img src="{{ asset('images/autobitacoraLogo.png') }}" alt="AutoBitacora" height="28" class="me-2 rounded bg-white p-1">
                        <div>
                            <h6 class="modal-title fw-bold mb-0" id="viewAcceptedPolicyModalLabel"><x-section-icon name="policies" class="me-1" />{{ $currentPlatformPolicy->title }}</h6>
                            <small class="text-secondary">Versión {{ $currentPlatformPolicy->version }} &bull; Vigencia: {{ $currentPlatformPolicy->effective_date?->format('d/m/Y') }}</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4 bg-light" style="font-size: 0.88rem; line-height: 1.6;">
                    @if(isset($userPolicyAcceptance) && $userPolicyAcceptance)
                        <div class="alert alert-success d-flex align-items-center py-2 px-3 mb-3 small">
                            <span class="fs-5 me-2">&#10003;</span>
                            <div>
                                <strong>Documento Aceptado:</strong> Registraste tu aceptación el <strong>{{ $userPolicyAcceptance->accepted_at?->format('d/m/Y H:i:s') }}</strong> desde la IP <code>{{ $userPolicyAcceptance->ip_address ?? 'Registrada' }}</code>.
                            </div>
                        </div>
                    @endif

                    <div class="p-3 bg-white rounded border">
                        {!! Str::markdown($currentPlatformPolicy->content) !!}
                    </div>
                </div>
                <div class="modal-footer bg-white py-2">
                    <span class="text-muted small me-auto">AutoBitacora &bull; Privacidad y Seguridad Garantizada</span>
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endif
