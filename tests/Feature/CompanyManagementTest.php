<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $mechanic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'username' => 'superadmin_test',
            'role' => 'super_admin',
        ]);

        $this->mechanic = User::factory()->create([
            'username' => 'mechanic_test',
            'role' => 'mechanic',
        ]);
    }

    /**
     * Valida que un usuario no super_admin reciba 403.
     */
    public function test_non_super_admin_cannot_access_companies_module(): void
    {
        $response = $this->actingAs($this->mechanic)->get('/companies');

        $response->assertStatus(403);
    }

    /**
     * Valida que el super_admin pueda ver el listado de empresas.
     */
    public function test_super_admin_can_view_companies_list(): void
    {
        $company = Company::create([
            'name' => 'Taller AutoFix',
            'slug' => 'taller-autofix',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->get('/companies');

        $response->assertStatus(200);
        $response->assertSee('Taller AutoFix');
        $response->assertSee('taller-autofix');
    }

    /**
     * Valida que el super_admin pueda crear una empresa.
     */
    public function test_super_admin_can_create_a_company(): void
    {
        $payload = [
            'name' => 'Concesionario Premier',
            'slug' => 'concesionario-premier',
            'tax_id' => '130999888',
            'email' => 'contacto@premier.do',
            'phone' => '809-555-1234',
            'status' => 'active',
            'country' => 'DO',
        ];

        $response = $this->actingAs($this->superAdmin)->post('/companies', $payload);

        $this->assertDatabaseHas('companies', [
            'name' => 'Concesionario Premier',
            'slug' => 'concesionario-premier',
            'tax_id' => '130999888',
        ]);

        $company = Company::where('slug', 'concesionario-premier')->first();
        $response->assertRedirect(route('companies.show', $company));
    }

    /**
     * Valida que el slug se genere automáticamente si se envía vacío.
     */
    public function test_slug_is_automatically_generated_when_omitted(): void
    {
        $payload = [
            'name' => 'Taller Mecánico Hermanos Pérez',
            'status' => 'active',
            'country' => 'DO',
        ];

        $response = $this->actingAs($this->superAdmin)->post('/companies', $payload);

        $this->assertDatabaseHas('companies', [
            'name' => 'Taller Mecánico Hermanos Pérez',
            'slug' => 'taller-mecanico-hermanos-perez',
        ]);

        $company = Company::where('slug', 'taller-mecanico-hermanos-perez')->first();
        $response->assertRedirect(route('companies.show', $company));
    }

    /**
     * Valida que el slug deba ser único.
     */
    public function test_slug_must_be_unique(): void
    {
        Company::create([
            'name' => 'Taller Existente',
            'slug' => 'taller-existente',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->post('/companies', [
            'name' => 'Otro Taller',
            'slug' => 'taller-existente',
            'status' => 'trial',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    /**
     * Valida que el super_admin pueda actualizar una empresa.
     */
    public function test_super_admin_can_update_a_company(): void
    {
        $company = Company::create([
            'name' => 'Taller Inicial',
            'slug' => 'taller-inicial',
            'status' => 'trial',
        ]);

        $response = $this->actingAs($this->superAdmin)->put("/companies/{$company->id}", [
            'name' => 'Taller Modificado',
            'status' => 'active',
            'country' => 'DO',
        ]);

        $response->assertRedirect(route('companies.show', $company));
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Taller Modificado',
            'slug' => 'taller-inicial', // El slug original se mantiene inmutable
            'status' => 'active',
        ]);
    }

    /**
     * Valida que el super_admin pueda aplicar soft delete a una empresa.
     */
    public function test_super_admin_can_delete_a_company(): void
    {
        $company = Company::create([
            'name' => 'Taller A Eliminar',
            'slug' => 'taller-a-eliminar',
            'status' => 'suspended',
        ]);

        $response = $this->actingAs($this->superAdmin)->delete("/companies/{$company->id}");

        $response->assertRedirect(route('companies.index'));
        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }
}
