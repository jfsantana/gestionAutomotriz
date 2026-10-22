<?php

namespace App\Http\Controllers;

use App\Domain\Companies\Models\Company;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StaffController extends Controller
{
    /**
     * Muestra el listado de personal interno filtrado según la empresa del usuario.
     */
    public function index(Request $request): View
    {
        $currentUser = $request->user();

        $query = User::query()
            ->with('company')
            ->where('role', 'mechanic');

        // Aislamiento multiempresa: company_admin solo ve usuarios de su propia empresa
        if ($currentUser->role === 'company_admin') {
            $query->where('company_id', $currentUser->company_id);
        }

        $staff = $query->latest('id')->paginate(10);

        return view('staff.index', compact('staff'));
    }

    /**
     * Formulario para crear un nuevo usuario operativo.
     */
    public function create(Request $request): View
    {
        $currentUser = $request->user();
        $companies = collect();

        if ($currentUser->role === 'super_admin') {
            $companies = Company::query()->orderBy('name')->get(['id', 'name', 'slug']);
        }

        $user = new User([
            'role' => 'mechanic',
            'is_active' => true,
            'company_id' => $currentUser->company_id,
        ]);

        return view('staff.create', compact('user', 'companies'));
    }

    /**
     * Almacena el nuevo usuario interno con su company_id garantizado.
     */
    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $currentUser = $request->user();
        $data = $request->validated();

        // Si es company_admin, el company_id se fuerza siempre al de su empresa
        if ($currentUser->role === 'company_admin') {
            $data['company_id'] = $currentUser->company_id;
        }

        $data['role'] = 'mechanic';
        $data['is_active'] = $request->boolean('is_active', true);
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return redirect()
            ->route('staff.index')
            ->with('success', "Personal '{$user->name}' ({$user->username}) registrado exitosamente.");
    }

    /**
     * Formulario para editar un usuario interno.
     */
    public function edit(Request $request, User $staff): View
    {
        $this->authorizeStaffAccess($request->user(), $staff);

        $companies = collect();
        if ($request->user()->role === 'super_admin') {
            $companies = Company::query()->orderBy('name')->get(['id', 'name', 'slug']);
        }

        return view('staff.edit', [
            'user' => $staff,
            'companies' => $companies,
        ]);
    }

    /**
     * Actualiza los datos del usuario interno.
     */
    public function update(UpdateStaffRequest $request, User $staff): RedirectResponse
    {
        $this->authorizeStaffAccess($request->user(), $staff);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', false);

        if ($request->user()->role === 'company_admin') {
            // Un company_admin jamás puede cambiar la empresa de un usuario
            unset($data['company_id']);
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $staff->update($data);

        return redirect()
            ->route('staff.index')
            ->with('success', "Personal '{$staff->username}' actualizado correctamente.");
    }

    /**
     * Alterna rápidamente el estado activo/inactivo del usuario.
     */
    public function toggleStatus(Request $request, User $staff): RedirectResponse
    {
        $this->authorizeStaffAccess($request->user(), $staff);

        $staff->is_active = ! $staff->is_active;
        $staff->save();

        $estado = $staff->is_active ? 'activado' : 'desactivado';

        return redirect()
            ->route('staff.index')
            ->with('success', "El acceso del usuario '{$staff->username}' fue {$estado}.");
    }

    /**
     * Valida de forma estricta el aislamiento por empresa y tipo de rol.
     */
    protected function authorizeStaffAccess(User $currentUser, User $target): void
    {
        // Solo aplica para mecánicos
        if ($target->role !== 'mechanic') {
            abort(404, 'Usuario de personal no encontrado.');
        }

        // Si es company_admin, debe pertenecer exactamente a su misma empresa
        if ($currentUser->role === 'company_admin' && (int)$target->company_id !== (int)$currentUser->company_id) {
            abort(403, 'No tienes autorización para acceder al personal de otra empresa.');
        }
    }
}
