@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label for="customer_id" class="form-label fw-semibold">Propietario / Cliente <span class="text-danger">*</span></label>
        <select class="form-select @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
            <option value="">-- Seleccionar Cliente --</option>
            @foreach($customers as $c)
                <option value="{{ $c->id }}" @selected((string)old('customer_id', $order->customer_id) === (string)$c->id)>
                    {{ $c->full_name }}
                </option>
            @endforeach
        </select>
        @error('customer_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="vehicle_id" class="form-label fw-semibold">Vehículo <span class="text-danger">*</span></label>
        <select class="form-select @error('vehicle_id') is-invalid @enderror" id="vehicle_id" name="vehicle_id" required>
            <option value="">-- Seleccionar Vehículo --</option>
            @foreach($vehicles as $v)
                <option 
                    value="{{ $v->id }}" 
                    data-customer-id="{{ $v->customer_id }}"
                    data-mileage="{{ $v->current_mileage }}"
                    @selected((string)old('vehicle_id', $order->vehicle_id) === (string)$v->id)
                >
                    {{ $v->plate }} - {{ $v->make }} {{ $v->model }} ({{ number_format($v->current_mileage) }} km)
                </option>
            @endforeach
        </select>
        @error('vehicle_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="service_id" class="form-label fw-semibold">Servicio desde Catálogo (Opcional)</label>
        <select class="form-select @error('service_id') is-invalid @enderror" id="service_id" name="service_id">
            <option value="">-- Personalizado / Sin catálogo --</option>
            @foreach($services as $s)
                <option 
                    value="{{ $s->id }}" 
                    data-name="{{ $s->name }}"
                    data-type="{{ $s->maintenance_type }}"
                    data-price="{{ $s->default_price }}"
                    data-days="{{ $s->default_interval_days }}"
                    data-mileage="{{ $s->default_interval_mileage }}"
                    data-desc="{{ $s->description }}"
                    @selected((string)old('service_id', $order->service_id) === (string)$s->id)
                >
                    {{ $s->name }} [{{ $s->maintenance_type === 'preventive' ? 'Preventivo' : 'Correctivo' }}]
                    @if($s->default_price) (${{ number_format($s->default_price, 2) }}) @endif
                </option>
            @endforeach
        </select>
        <div class="form-text">Si seleccionas un servicio, se autocompletarán los datos sugeridos.</div>
        @error('service_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="mechanic_id" class="form-label fw-semibold">Mecánico Responsable</label>
        <select class="form-select @error('mechanic_id') is-invalid @enderror" id="mechanic_id" name="mechanic_id">
            <option value="">-- Sin asignar / General --</option>
            @foreach($mechanics as $m)
                <option value="{{ $m->id }}" @selected((string)old('mechanic_id', $order->mechanic_id) === (string)$m->id)>
                    {{ $m->name }} ({{ $m->username }})
                </option>
            @endforeach
        </select>
        @error('mechanic_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="service_name_snapshot" class="form-label fw-semibold">Concepto / Nombre del Servicio <span class="text-danger">*</span></label>
        <input 
            type="text" 
            class="form-control @error('service_name_snapshot') is-invalid @enderror" 
            id="service_name_snapshot" 
            name="service_name_snapshot" 
            value="{{ old('service_name_snapshot', $order->service_name_snapshot) }}" 
            placeholder="Ej. Cambio de Aceite 5W-30 y Filtro"
            required
        >
        @error('service_name_snapshot')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="maintenance_type" class="form-label fw-semibold">Tipo de Mantenimiento <span class="text-danger">*</span></label>
        <select class="form-select @error('maintenance_type') is-invalid @enderror" id="maintenance_type" name="maintenance_type" required>
            <option value="preventive" @selected(old('maintenance_type', $order->maintenance_type) === 'preventive')>Preventivo</option>
            <option value="corrective" @selected(old('maintenance_type', $order->maintenance_type) === 'corrective')>Correctivo</option>
        </select>
        @error('maintenance_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label for="status" class="form-label fw-semibold">Estado de la Orden <span class="text-danger">*</span></label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            <option value="open" @selected(old('status', $order->status) === 'open')>Abierta (Open)</option>
            <option value="in_progress" @selected(old('status', $order->status) === 'in_progress')>En Progreso</option>
            <option value="completed" @selected(old('status', $order->status) === 'completed')>Completada</option>
            <option value="cancelled" @selected(old('status', $order->status) === 'cancelled')>Cancelada</option>
        </select>
        @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="service_date" class="form-label fw-semibold">Fecha del Servicio <span class="text-danger">*</span></label>
        <input 
            type="date" 
            class="form-control @error('service_date') is-invalid @enderror" 
            id="service_date" 
            name="service_date" 
            value="{{ old('service_date', $order->service_date?->format('Y-m-d') ?? now()->toDateString()) }}" 
            required
        >
        @error('service_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="current_mileage" class="form-label fw-semibold">Kilometraje al Entrar (km) <span class="text-danger">*</span></label>
        <input 
            type="number" 
            class="form-control @error('current_mileage') is-invalid @enderror" 
            id="current_mileage" 
            name="current_mileage" 
            min="0"
            value="{{ old('current_mileage', $order->current_mileage ?? 0) }}" 
            required
        >
        @error('current_mileage')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="cost" class="form-label fw-semibold">Costo Total ($)</label>
        <input 
            type="number" 
            step="0.01" 
            min="0"
            class="form-control @error('cost') is-invalid @enderror" 
            id="cost" 
            name="cost" 
            value="{{ old('cost', $order->cost) }}" 
            placeholder="0.00"
        >
        @error('cost')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="next_due_date" class="form-label fw-semibold">Próximo Vencimiento (Fecha)</label>
        <input 
            type="date" 
            class="form-control @error('next_due_date') is-invalid @enderror" 
            id="next_due_date" 
            name="next_due_date" 
            value="{{ old('next_due_date', $order->next_due_date?->format('Y-m-d')) }}"
        >
        <div class="form-text">Base para futuros recordatorios de mantenimiento.</div>
        @error('next_due_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="next_due_mileage" class="form-label fw-semibold">Próximo Vencimiento (Kilometraje)</label>
        <input 
            type="number" 
            class="form-control @error('next_due_mileage') is-invalid @enderror" 
            id="next_due_mileage" 
            name="next_due_mileage" 
            min="0"
            value="{{ old('next_due_mileage', $order->next_due_mileage) }}" 
            placeholder="Ej. 50000"
        >
        <div class="form-text">Km estimados para la próxima revisión.</div>
        @error('next_due_mileage')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="technical_notes" class="form-label fw-semibold">Observaciones Técnicas (Internas)</label>
        <textarea 
            class="form-control @error('technical_notes') is-invalid @enderror" 
            id="technical_notes" 
            name="technical_notes" 
            rows="3" 
            placeholder="Detalles técnicos del mecánico, estado de piezas, torque aplicado..."
        >{{ old('technical_notes', $order->technical_notes) }}</textarea>
        @error('technical_notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="customer_notes" class="form-label fw-semibold">Observaciones para el Cliente</label>
        <textarea 
            class="form-control @error('customer_notes') is-invalid @enderror" 
            id="customer_notes" 
            name="customer_notes" 
            rows="3" 
            placeholder="Recomendaciones entregadas al cliente en la recepción o entrega..."
        >{{ old('customer_notes', $order->customer_notes) }}</textarea>
        @error('customer_notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="photos" class="form-label fw-semibold">Evidencia Visual / Fotografías (Opcional)</label>
        <input 
            type="file" 
            class="form-control @error('photos') is-invalid @enderror" 
            id="photos" 
            name="photos[]" 
            multiple 
            accept="image/*"
        >
        <div class="form-text">Puedes subir imágenes de evidencia (repuestos nuevos, estado de piezas, etc.). Máx 5MB por foto.</div>
        @error('photos')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
    <a href="{{ route('maintenance-orders.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary px-4 fw-semibold">Guardar Orden de Mantenimiento</button>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const customerSelect = document.getElementById('customer_id');
        const vehicleSelect = document.getElementById('vehicle_id');
        const serviceSelect = document.getElementById('service_id');
        const nameInput = document.getElementById('service_name_snapshot');
        const typeSelect = document.getElementById('maintenance_type');
        const costInput = document.getElementById('cost');
        const mileageInput = document.getElementById('current_mileage');
        const dateInput = document.getElementById('service_date');
        const nextDateInput = document.getElementById('next_due_date');
        const nextMileageInput = document.getElementById('next_due_mileage');

        // Filtrar vehículos según cliente seleccionado
        function filterVehicles() {
            const customerId = customerSelect.value;
            const options = vehicleSelect.querySelectorAll('option');

            options.forEach(opt => {
                if (!opt.value) return;
                const vCustId = opt.getAttribute('data-customer-id');
                if (!customerId || vCustId === customerId) {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                    if (opt.selected) {
                        vehicleSelect.value = '';
                    }
                }
            });
        }

        customerSelect.addEventListener('change', filterVehicles);

        // Al cambiar vehículo, sugerir odómetro
        vehicleSelect.addEventListener('change', function () {
            const selectedOpt = vehicleSelect.options[vehicleSelect.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                const km = selectedOpt.getAttribute('data-mileage');
                if (km && (!mileageInput.value || mileageInput.value === '0')) {
                    mileageInput.value = km;
                }
                const cId = selectedOpt.getAttribute('data-customer-id');
                if (cId && !customerSelect.value) {
                    customerSelect.value = cId;
                }
            }
        });

        // Al seleccionar servicio desde catálogo, sugerir datos
        if (serviceSelect) {
            serviceSelect.addEventListener('change', function () {
                const opt = serviceSelect.options[serviceSelect.selectedIndex];
                if (!opt || !opt.value) return;

                if (!nameInput.value || nameInput.value === 'Mantenimiento General') {
                    nameInput.value = opt.getAttribute('data-name');
                }
                const sType = opt.getAttribute('data-type');
                if (sType) typeSelect.value = sType;

                const price = opt.getAttribute('data-price');
                if (price && !costInput.value) costInput.value = price;

                const days = parseInt(opt.getAttribute('data-days'), 10);
                if (days && dateInput.value && !nextDateInput.value) {
                    const d = new Date(dateInput.value);
                    d.setDate(d.getDate() + days);
                    nextDateInput.value = d.toISOString().split('T')[0];
                }

                const sMileage = parseInt(opt.getAttribute('data-mileage'), 10);
                const curKm = parseInt(mileageInput.value, 10) || 0;
                if (sMileage && !nextMileageInput.value) {
                    nextMileageInput.value = curKm + sMileage;
                }
            });
        }

        // Ejecutar filtro inicial si ya hay cliente preseleccionado
        if (customerSelect.value) {
            filterVehicles();
        }
    });
</script>
@endpush
