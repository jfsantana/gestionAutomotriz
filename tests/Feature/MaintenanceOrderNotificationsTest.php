<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Vehicles\Models\Vehicle;
use App\Mail\MaintenanceOrderNotificationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class MaintenanceOrderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $admin;

    protected Customer $customer;

    protected Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Taller Los Prados',
            'slug' => 'taller-los-prados',
            'status' => 'active',
            'phone' => '809-555-0100',
        ]);
        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'company_admin',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);
        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'full_name' => 'Ana Propietaria',
            'email' => 'ana@example.com',
        ]);
        $this->vehicle = Vehicle::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'plate' => 'A123456',
            'make' => 'Toyota',
            'model' => 'RAV4',
            'year' => 2023,
            'current_mileage' => 10000,
        ]);
    }

    #[TestWith(['open'])]
    #[TestWith(['in_progress'])]
    #[TestWith(['completed'])]
    #[TestWith(['cancelled'])]
    public function test_creation_in_any_initial_status_notifies_owner_with_admin_copy(string $status): void
    {
        Mail::fake();

        $response = $this->actingAs($this->admin)->post(route('maintenance-orders.store'), $this->payload($status));

        $response->assertSessionHasNoErrors()->assertSessionMissing('warning');
        $order = MaintenanceOrder::sole();
        $response->assertRedirect(route('maintenance-orders.show', $order));
        $this->assertSame($status, $order->status);
        $this->assertSame(12000, $this->vehicle->fresh()->current_mileage);
        $this->assertSame($status === 'completed', $order->completed_at !== null);
        $this->assertSame($status === 'cancelled', $order->cancelled_at !== null);
        Mail::assertSentCount(1);
        Mail::assertSent(MaintenanceOrderNotificationMail::class, fn ($mail) => $mail->hasTo('ana@example.com')
            && $mail->hasCc('admin@example.com')
            && $mail->order->id === $order->id
            && $mail->order->status === $status
            && $mail->previousStatus === null);
    }

    public static function statusTransitions(): array
    {
        $cases = [];
        foreach (['open', 'in_progress', 'completed', 'cancelled'] as $from) {
            foreach (['open', 'in_progress', 'completed', 'cancelled'] as $to) {
                if ($from !== $to) {
                    $cases["{$from} to {$to}"] = [$from, $to];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('statusTransitions')]
    public function test_each_real_status_change_notifies_owner_and_admin(string $from, string $to): void
    {
        $order = $this->createOrder($from);
        Mail::fake();

        $response = $this->actingAs($this->admin)->put(route('maintenance-orders.update', $order), $this->payload($to));

        $response->assertRedirect(route('maintenance-orders.show', $order))->assertSessionMissing('warning');
        $this->assertSame($to, $order->fresh()->status);
        Mail::assertSentCount(1);
        Mail::assertSent(MaintenanceOrderNotificationMail::class, fn ($mail) => $mail->hasTo('ana@example.com')
            && $mail->hasCc('admin@example.com')
            && $mail->previousStatus === $from
            && $mail->order->status === $to);
    }

    public function test_edit_without_status_change_does_not_send_another_email(): void
    {
        $order = $this->createOrder();
        Mail::fake();

        $response = $this->actingAs($this->admin)->put(route('maintenance-orders.update', $order), [
            ...$this->payload(),
            'customer_notes' => 'Nueva observación',
        ]);

        $response->assertRedirect(route('maintenance-orders.show', $order));
        $this->assertSame('Nueva observación', $order->fresh()->customer_notes);
        Mail::assertNothingSent();
    }

    #[TestWith(['mechanic'])]
    #[TestWith(['super_admin'])]
    public function test_creation_by_other_staff_roles_still_copies_company_admin(string $role): void
    {
        $staff = User::factory()->create([
            'company_id' => $role === 'super_admin' ? null : $this->company->id,
            'role' => $role,
            'is_active' => true,
        ]);
        Mail::fake();

        $this->actingAs($staff)->post(route('maintenance-orders.store'), $this->payload())
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        Mail::assertSent(MaintenanceOrderNotificationMail::class, fn ($mail) => $mail->hasTo('ana@example.com')
            && $mail->hasCc('admin@example.com') && ! $mail->hasCc($staff->email));
    }

    public function test_copies_only_active_admins_of_the_order_company_without_duplicate_recipients(): void
    {
        User::factory()->create(['company_id' => $this->company->id, 'role' => 'company_admin', 'is_active' => true, 'email' => 'second@example.com']);
        User::factory()->create(['company_id' => $this->company->id, 'role' => 'company_admin', 'is_active' => true, 'email' => 'ana@example.com']);
        User::factory()->create(['company_id' => $this->company->id, 'role' => 'company_admin', 'is_active' => false, 'email' => 'inactive@example.com']);
        User::factory()->create(['company_id' => $this->company->id, 'role' => 'mechanic', 'is_active' => true, 'email' => 'mechanic@example.com']);
        $otherCompany = Company::create(['name' => 'Otro taller', 'slug' => 'otro-taller', 'status' => 'active']);
        User::factory()->create(['company_id' => $otherCompany->id, 'role' => 'company_admin', 'is_active' => true, 'email' => 'other@example.com']);
        Mail::fake();

        $this->actingAs($this->admin)->post(route('maintenance-orders.store'), $this->payload())
            ->assertSessionMissing('warning');

        Mail::assertSentCount(1);
        Mail::assertSent(MaintenanceOrderNotificationMail::class, function ($mail) {
            $this->assertCount(1, $mail->to);
            $this->assertCount(2, $mail->cc);

            return $mail->hasTo('ana@example.com') && $mail->hasCc('admin@example.com')
                && $mail->hasCc('second@example.com');
        });
    }

    #[TestWith([null])]
    #[TestWith(['invalid-email'])]
    public function test_missing_or_invalid_customer_email_keeps_order_and_warns_without_sending(?string $email): void
    {
        $this->customer->update(['email' => $email]);
        Mail::fake();

        $this->actingAs($this->admin)->post(route('maintenance-orders.store'), $this->payload())
            ->assertSessionHas('success')
            ->assertSessionHas('warning', 'El mantenimiento quedó guardado, pero no se envió el correo porque el propietario no tiene un email válido. Actualiza los datos del cliente.');

        $this->assertDatabaseCount('maintenance_orders', 1);
        Mail::assertNothingSent();
    }

    public function test_missing_admin_email_still_sends_to_owner_and_warns_about_missing_copy(): void
    {
        $this->admin->update(['email' => '']);
        Mail::fake();

        $this->actingAs($this->admin)->post(route('maintenance-orders.store'), $this->payload())
            ->assertSessionHas('warning', 'Se envió el correo al propietario, pero sin copia: la empresa no tiene un administrador activo con email válido.');

        Mail::assertSent(MaintenanceOrderNotificationMail::class, fn ($mail) => $mail->hasTo('ana@example.com') && $mail->cc === []);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_transport_failure_is_reported_after_saving_without_losing_changes(bool $updating): void
    {
        $order = $updating ? $this->createOrder() : null;
        $transactionLevel = DB::transactionLevel();
        Exceptions::fake();
        $pending = Mockery::mock(PendingMail::class);
        Mail::shouldReceive('to')->once()->with('ana@example.com')->andReturn($pending);
        $pending->shouldReceive('cc')->once()->with(['admin@example.com'])->andReturnSelf();
        $pending->shouldReceive('send')->once()->andReturnUsing(function () use ($transactionLevel) {
            $this->assertSame($transactionLevel, DB::transactionLevel());
            $this->assertDatabaseHas('maintenance_orders', ['status' => 'completed']);
            $this->assertSame(12000, $this->vehicle->fresh()->current_mileage);

            throw new TransportException('SMTP unavailable');
        });

        $response = $updating
            ? $this->actingAs($this->admin)->put(route('maintenance-orders.update', $order), $this->payload('completed'))
            : $this->actingAs($this->admin)->post(route('maintenance-orders.store'), $this->payload('completed'));

        $response->assertRedirect(route('maintenance-orders.show', MaintenanceOrder::sole()))
            ->assertSessionHas('success')
            ->assertSessionHas('warning', 'El mantenimiento quedó guardado, pero no se pudo enviar el correo. Revisa la configuración del servicio de correo; el cliente podría no haber recibido la notificación.');
        $this->assertDatabaseHas('maintenance_orders', ['status' => 'completed']);
        Exceptions::assertReported(TransportException::class);
    }

    public function test_invalid_initial_status_does_not_save_or_send(): void
    {
        Mail::fake();

        $this->actingAs($this->admin)->post(route('maintenance-orders.store'), $this->payload('invalid'))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('maintenance_orders', 0);
        Mail::assertNothingSent();
    }

    public function test_other_company_cannot_change_status_or_trigger_email(): void
    {
        $order = $this->createOrder();
        $company = Company::create(['name' => 'Otro taller', 'slug' => 'otro-taller', 'status' => 'active']);
        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'company_admin', 'is_active' => true]);
        Mail::fake();

        $this->actingAs($admin)->put(route('maintenance-orders.update', $order), $this->payload('completed'))
            ->assertForbidden();

        $this->assertSame('open', $order->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_personal_maintenance_creation_and_changes_do_not_notify_workshop(): void
    {
        $client = User::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'role' => 'customer',
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);
        Mail::fake();

        $this->actingAs($client)->post(route('portal.maintenance.store'), [
            ...$this->payload('completed'), 'cost' => 0, 'customer_notes' => 'Registro personal',
        ])->assertRedirect(route('portal.maintenance.index'));
        $order = MaintenanceOrder::sole();
        $this->assertSame('customer', $order->origin);
        $this->actingAs($this->admin)->put(route('maintenance-orders.update', $order), $this->payload('cancelled'))
            ->assertRedirect(route('maintenance-orders.show', $order));

        $this->assertSame('cancelled', $order->fresh()->status);
        Mail::assertNothingSent();
    }

    #[TestWith(['open', 'Abierta', 'Tu mantenimiento está registrado.'])]
    #[TestWith(['in_progress', 'En progreso', 'El equipo está trabajando'])]
    #[TestWith(['completed', 'Completada', 'El mantenimiento ha sido completado.'])]
    #[TestWith(['cancelled', 'Cancelada', 'Este mantenimiento fue cancelado.'])]
    public function test_email_content_is_clear_in_html_and_text_for_every_status(string $status, string $label, string $message): void
    {
        $order = $this->createOrder($status);
        $mail = new MaintenanceOrderNotificationMail($order);

        $mail->assertHasSubject("Mantenimiento registrado: {$label} | A123456 | Taller Los Prados");
        foreach (['Ana Propietaria', 'Toyota RAV4', 'A123456', 'Cambio de aceite', 'ORD-TEST-0001', '24/10/2026', $label, $message, 'Ver mis mantenimientos', route('portal.maintenance.index'), 'invitación', '809-555-0100'] as $text) {
            $mail->assertSeeInHtml($text);
            $mail->assertSeeInText($text);
        }
        $mail->assertDontSeeInHtml('Estado anterior:');
        $mail->assertDontSeeInHtml('Nota interna confidencial');
        $mail->assertDontSeeInText('Nota interna confidencial');
    }

    public function test_changed_status_email_shows_previous_state_and_escapes_user_content(): void
    {
        $order = $this->createOrder('completed');
        $order->service_name_snapshot = '<script>alert("unsafe")</script>';
        $mail = new MaintenanceOrderNotificationMail($order, 'in_progress');

        $mail->assertHasSubject('Actualización de mantenimiento: Completada | A123456 | Taller Los Prados');
        $mail->assertSeeInHtml('Estado anterior: En progreso');
        $mail->assertSeeInText('Estado anterior: En progreso');
        $mail->assertSeeInHtml($order->service_name_snapshot);
        $mail->assertDontSeeInHtml('<script>', false);
        $mail->assertSeeInHtml('href="'.route('portal.maintenance.index').'"', false);
    }

    private function payload(string $status = 'open'): array
    {
        return [
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'maintenance_type' => 'preventive',
            'service_name_snapshot' => 'Cambio de aceite',
            'service_date' => '2026-10-24',
            'current_mileage' => 12000,
            'status' => $status,
            'technical_notes' => 'Nota interna confidencial',
        ];
    }

    private function createOrder(string $status = 'open'): MaintenanceOrder
    {
        return MaintenanceOrder::create([
            ...$this->payload($status),
            'company_id' => $this->company->id,
            'order_number' => 'ORD-TEST-0001',
            'origin' => 'workshop',
        ]);
    }
}
