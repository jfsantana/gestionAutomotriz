<?php

namespace App\Http\Controllers;

use App\Domain\Companies\Models\Company;
use App\Http\Requests\CompanyAdmins\StoreCompanyAdminRequest;
use App\Http\Requests\CompanyAdmins\UpdateCompanyAdminRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CompanyAdminController extends Controller
{
    /**
     * Muestra el listado de usuarios administradores de empresa.
     */
    public function index(): View
    {
        $admins = User::query()
            ->with('company')
            ->where('role', 'company_admin')
            ->latest('id')
            ->paginate(10);

        return view('company_admins.index', compact('admins'));
    }

    /**
     * Muestra el formulario para crear un nuevo company_admin.
     */
    public function create(): View
    {
        $companies = Company::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'status']);

        $user = new User(['is_active' => true]);

        return view('company_admins.create', compact('companies', 'user'));
    }

    /**
     * Almacena un nuevo administrador de empresa en la base de datos.
     */
    public function store(StoreCompanyAdminRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['role'] = 'company_admin';
        $data['is_active'] = $request->boolean('is_active', true);
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return redirect()
            ->route('company-admins.show', $user)
            ->with('success', "Administrador '{$user->username}' creado y vinculado exitosamente.");
    }

    /**
     * Muestra el detalle del administrador de empresa.
     */
    public function show(User $company_admin): View
    {
        $this->ensureIsCompanyAdmin($company_admin);
        $company_admin->load('company');

        return view('company_admins.show', ['user' => $company_admin]);
    }

    /**
     * Muestra el formulario para editar un administrador de empresa.
     */
    public function edit(User $company_admin): View
    {
        $this->ensureIsCompanyAdmin($company_admin);

        $companies = Company::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'status']);

        return view('company_admins.edit', [
            'user' => $company_admin,
            'companies' => $companies,
        ]);
    }

    /**
     * Actualiza los datos del administrador de empresa.
     */
    public function update(UpdateCompanyAdminRequest $request, User $company_admin): RedirectResponse
    {
        $this->ensureIsCompanyAdmin($company_admin);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', false);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $company_admin->update($data);

        return redirect()
            ->route('company-admins.show', $company_admin)
            ->with('success', "Los datos del administrador '{$company_admin->username}' fueron actualizados.");
    }

    /**
     * Valida que el modelo User corresponda a un company_admin.
     */
    protected function ensureIsCompanyAdmin(User $user): void
    {
        if ($user->role !== 'company_admin') {
            abort(404, 'Usuario administrador no encontrado.');
        }
    }
}
