<?php

namespace Tests\Feature;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Vehicles\Models\Vehicle;
use App\Mail\MaintenanceReminderMail;
use App\Models\User;
use App\Services\MaintenanceReminderNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Mockery;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class MaintenanceReminderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Customer $customer;

    protected Vehicle $vehicle;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-24 12:00:00', 'UTC'));
        $this->company = Company::create([
            'name' => 'Taller Caracas',
            'slug' => 'taller-caracas',
            'status' => 'active',
            'phone' => '0212-555-0100',
            'email' => 'taller@example.com',
            'address' => 'Avenida Principal',
        ]);
        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'full_name' => 'Ana Propietaria',
            'email' => 'ana@example.com',
        ]);
        $this->vehicle = Vehicle::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'plate' => 'ABC123',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'current_mileage' => 10000,
        ]);
        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'company_admin',
            'is_active' => true,
        ]);
    }

    public function test_automatic_delivery_selects_exactly_two_calendar_days_and_is_repeatable(): void
    {
        Mail::fake();
        $due = $this->order();
        foreach (['2026-10-23', '2026-10-24', '2026-10-25', '2026-10-27', null] as $date) {
            $this->order(['next_due_date' => $date]);
        }
        foreach (['open', 'in_progress', 'cancelled'] as $status) {
            $this->order(['status' => $status]);
        }
        $this->order(['origin' => 'customer']);
        $this->order()->delete();

        $this->artisan('maintenance:send-reminders')->assertSuccessful();
        $this->artisan('maintenance:send-reminders')->assertSuccessful();

        Mail::assertSentCount(1);
        Mail::assertSent(MaintenanceReminderMail::class, fn ($mail) => $mail->order->id === $due->id
            && $mail->hasTo('ana@example.com') && $mail->cc === [] && $mail->bcc === []);
        $this->assertNotNull($due->fresh()->reminder_sent_at);
        $this->assertSame(1, MaintenanceOrder::whereNotNull('reminder_sent_at')->count());
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function test_manual_and_automatic_delivery_share_the_daily_limit(bool $manualFirst): void
    {
        Mail::fake();
        $order = $this->order();
        $this->actingAs($this->admin);

        if ($manualFirst) {
            $this->from(route('reminders.index'))->post(route('reminders.send', $order))
                ->assertRedirect(route('reminders.index'))->assertSessionHas('success');
            $this->artisan('maintenance:send-reminders')->assertSuccessful();
        } else {
            $this->artisan('maintenance:send-reminders')->assertSuccessful();
            $this->post(route('reminders.send', $order))
                ->assertSessionHas('info', MaintenanceReminderNotificationService::ALREADY_SENT);
        }

        $this->post(route('reminders.send', $order))->assertSessionHas('info');
        Mail::assertSentCount(1);
        $this->get(route('reminders.index'))->assertOk()
            ->assertSee('Último envío: 24/10/2026 08:00')
            ->assertSee(MaintenanceReminderNotificationService::ALREADY_SENT);
    }

    public function test_daily_limit_resets_at_caracas_midnight_not_utc_midnight(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->actingAs($this->admin);
        $this->travelTo(Carbon::parse('2026-10-25 03:59:00', 'UTC'));
        $this->post(route('reminders.send', $order))->assertSessionHas('success');
        $this->post(route('reminders.send', $order))->assertSessionHas('info');
        Mail::assertSentCount(1);

        $this->travelTo(Carbon::parse('2026-10-25 04:00:00', 'UTC'));
        $this->post(route('reminders.send', $order))->assertSessionHas('success');

        Mail::assertSentCount(2);
    }

    public function test_automatic_selection_uses_caracas_date_even_when_utc_is_next_day(): void
    {
        Mail::fake();
        $this->travelTo(Carbon::parse('2026-10-25 02:00:00', 'UTC'));
        $due = $this->order();
        $this->order(['next_due_date' => '2026-10-27']);

        $this->artisan('maintenance:send-reminders')->assertSuccessful();

        Mail::assertSentCount(1);
        Mail::assertSent(MaintenanceReminderMail::class, fn ($mail) => $mail->order->id === $due->id);
    }

    #[TestWith(['mechanic'])]
    #[TestWith(['customer'])]
    #[TestWith(['super_admin'])]
    #[TestWith(['company_admin', false])]
    public function test_disallowed_roles_and_inactive_admins_cannot_send(string $role, bool $active = true): void
    {
        Mail::fake();
        $order = $this->order();
        $user = User::factory()->create([
            'role' => $role,
            'company_id' => $this->company->id,
            'is_active' => $active,
        ]);

        $this->assertFalse(Gate::forUser($user)->allows('send-maintenance-reminder', $order));
        $this->actingAs($user)->post(route('reminders.send', $order))->assertForbidden();
        Mail::assertNothingSent();
        $this->assertNull($order->fresh()->reminder_sent_at);
    }

    public function test_other_company_admin_cannot_send_or_see_the_action(): void
    {
        Mail::fake();
        $order = $this->order();
        $otherCompany = Company::create(['name' => 'Otro taller', 'slug' => 'otro', 'status' => 'active']);
        $otherAdmin = User::factory()->create(['role' => 'company_admin', 'company_id' => $otherCompany->id]);

        $this->assertFalse(Gate::forUser($otherAdmin)->allows('send-maintenance-reminder', $order));
        $this->actingAs($otherAdmin)->post(route('reminders.send', $order))->assertForbidden();
        $this->get(route('reminders.index'))->assertOk()->assertDontSee(route('reminders.send', $order), false);
        Mail::assertNothingSent();
    }

    public function test_guest_is_redirected_to_login_without_sending(): void
    {
        Mail::fake();
        $order = $this->order();

        $this->post(route('reminders.send', $order))->assertRedirect(route('login'));

        Mail::assertNothingSent();
    }

    public function test_button_is_visible_only_for_own_company_admin(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin)->get(route('reminders.index'))->assertOk()
            ->assertSee(route('reminders.send', $order), false)->assertSee('Enviar recordatorio');
        foreach (['mechanic', 'super_admin'] as $role) {
            $user = User::factory()->create(['role' => $role, 'company_id' => $this->company->id]);
            $this->actingAs($user)->get(route('reminders.index'))->assertOk()
                ->assertDontSee(route('reminders.send', $order), false);
        }
    }

    #[TestWith(['open', 'workshop', '2026-10-26', 15000])]
    #[TestWith(['cancelled', 'workshop', '2026-10-26', 15000])]
    #[TestWith(['completed', 'customer', '2026-10-26', 15000])]
    #[TestWith(['completed', 'workshop', null, null])]
    public function test_manual_delivery_rejects_ineligible_orders(string $status, string $origin, ?string $date, ?int $mileage): void
    {
        Mail::fake();
        $order = $this->order(['status' => $status, 'origin' => $origin, 'next_due_date' => $date, 'next_due_mileage' => $mileage]);

        $this->actingAs($this->admin)->post(route('reminders.send', $order))
            ->assertSessionHasErrors(['reminder' => 'Solo se pueden recordar mantenimientos del taller completados con un próximo vencimiento.']);

        Mail::assertNothingSent();
        $this->assertNull($order->fresh()->reminder_sent_at);
    }

    #[TestWith([null])]
    #[TestWith(['invalid-email'])]
    public function test_invalid_email_is_visible_and_does_not_record_delivery(?string $email): void
    {
        Mail::fake();
        $order = $this->order();
        $this->customer->update(['email' => $email]);
        $message = 'El cliente no tiene un email válido. Actualiza sus datos antes de enviar el recordatorio.';

        $this->actingAs($this->admin)->post(route('reminders.send', $order))
            ->assertSessionHasErrors(['reminder' => $message]);
        $this->get(route('reminders.index'))->assertOk()->assertSee($message);
        $this->artisan('maintenance:send-reminders')->assertFailed();

        Mail::assertNothingSent();
        $this->assertNull($order->fresh()->reminder_sent_at);
    }

    #[TestWith(['vehicle_deleted'])]
    #[TestWith(['customer_deleted'])]
    #[TestWith(['company_deleted'])]
    #[TestWith(['vehicle_inactive'])]
    #[TestWith(['company_inactive'])]
    #[TestWith(['owner_changed'])]
    public function test_unavailable_or_mismatched_records_are_not_notified(string $scenario): void
    {
        Mail::fake();
        $order = $this->order();
        match ($scenario) {
            'vehicle_deleted' => $this->vehicle->delete(),
            'customer_deleted' => $this->customer->delete(),
            'company_deleted' => $this->company->delete(),
            'vehicle_inactive' => $this->vehicle->update(['customer_status' => 'INACTIVE']),
            'company_inactive' => $this->company->update(['status' => 'suspended']),
            'owner_changed' => DB::table('vehicles')->where('id', $this->vehicle->id)->update(['customer_id' => Customer::create([
                'company_id' => $this->company->id, 'full_name' => 'Otro propietario', 'email' => 'otro@example.com',
            ])->id]),
        };

        $this->artisan('maintenance:send-reminders')->assertFailed();

        Mail::assertNothingSent();
        $this->assertNull($order->fresh()->reminder_sent_at);
    }

    public function test_transport_failure_is_reported_without_success_and_can_be_retried(): void
    {
        $order = $this->order();
        Exceptions::fake();
        $mailManager = Mail::getFacadeRoot();
        $exception = new TransportException('SMTP unavailable');
        $pending = Mockery::mock(PendingMail::class);
        Mail::shouldReceive('to')->once()->with('ana@example.com')->andReturn($pending);
        $pending->shouldReceive('send')->once()->with(Mockery::type(MaintenanceReminderMail::class))->andThrow($exception);

        $this->actingAs($this->admin)->post(route('reminders.send', $order))
            ->assertSessionHas('error', MaintenanceReminderNotificationService::DELIVERY_ERROR)
            ->assertSessionMissing('success');

        Exceptions::assertReported(fn (TransportException $reported) => $reported === $exception);
        $this->assertNull($order->fresh()->reminder_sent_at);
        Mail::swap($mailManager);
        Mail::fake();
        $this->post(route('reminders.send', $order))->assertSessionHas('success');
        Mail::assertSentCount(1);
        $this->assertNotNull($order->fresh()->reminder_sent_at);
    }

    public function test_command_continues_after_transport_failure_and_returns_failure(): void
    {
        $first = $this->order();
        $second = $this->order();
        Exceptions::fake();
        $pending = Mockery::mock(PendingMail::class);
        Mail::shouldReceive('to')->twice()->andReturn($pending);
        $pending->shouldReceive('send')->once()->ordered()->andThrow(new TransportException('SMTP unavailable'));
        $pending->shouldReceive('send')->once()->ordered()->andReturnNull();

        $this->artisan('maintenance:send-reminders')
            ->expectsOutput('Enviados: 1 | Omitidos: 0 | Fallidos: 1')->assertFailed();

        $this->assertNull($first->fresh()->reminder_sent_at);
        $this->assertNotNull($second->fresh()->reminder_sent_at);
        Exceptions::assertReported(TransportException::class);
    }

    public function test_company_option_limits_delivery_and_rejects_invalid_input(): void
    {
        Mail::fake();
        $order = $this->order();

        $this->artisan('maintenance:send-reminders', ['--company' => $this->company->id + 1])->assertSuccessful();
        Mail::assertNothingSent();
        $this->artisan('maintenance:send-reminders', ['--company' => 'not-an-id'])->assertFailed();
        $this->artisan('maintenance:send-reminders', ['--company' => $this->company->id])->assertSuccessful();
        Mail::assertSentCount(1);
        $this->assertNotNull($order->fresh()->reminder_sent_at);
    }

    public function test_suspended_subscription_blocks_automatic_delivery_even_for_active_company(): void
    {
        Mail::fake();
        $order = $this->order();
        $plan = SubscriptionPlan::create(['name' => 'Plan de prueba', 'slug' => 'test-plan', 'monthly_price' => 10]);
        $this->company->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'suspended',
            'starts_at' => '2026-10-01',
        ]);

        $this->artisan('maintenance:send-reminders')->assertFailed();

        Mail::assertNothingSent();
        $this->assertNull($order->fresh()->reminder_sent_at);
    }

    public function test_changing_due_date_does_not_bypass_daily_limit(): void
    {
        Mail::fake();
        $order = $this->order(['next_due_date' => '2026-10-28']);
        $this->actingAs($this->admin)->post(route('reminders.send', $order))->assertSessionHas('success');
        $order->update(['next_due_date' => '2026-10-26']);

        $this->artisan('maintenance:send-reminders')->assertSuccessful();

        Mail::assertSentCount(1);
    }

    #[TestWith(['2026-10-26', 'Tu mantenimiento vence en 2 días.'])]
    #[TestWith(['2026-10-25', 'Tu mantenimiento vence mañana.'])]
    #[TestWith(['2026-10-24', 'Tu mantenimiento vence hoy.'])]
    #[TestWith(['2026-10-23', 'La fecha recomendada de tu mantenimiento ya pasó.'])]
    #[TestWith([null, 'Tu próximo mantenimiento está programado por kilometraje.'])]
    public function test_mail_content_matches_the_actual_due_date_and_has_contact_and_portal(?string $dueDate, string $message): void
    {
        $order = $this->order(['next_due_date' => $dueDate])->load(['company', 'customer', 'vehicle']);
        $mail = new MaintenanceReminderMail($order);

        $mail->assertHasSubject('Recordatorio de mantenimiento | ABC123 | Taller Caracas');
        foreach ([$message, 'Ana Propietaria', 'Cambio de aceite', 'ABC123', '15.000 km', '0212-555-0100',
            'taller@example.com', 'Avenida Principal', route('portal.maintenance.index'), 'Este aviso no reserva una cita.'] as $text) {
            $mail->assertSeeInHtml($text);
            $mail->assertSeeInText($text);
        }
        if ($dueDate) {
            $mail->assertSeeInHtml(Carbon::parse($dueDate)->format('d/m/Y'));
            $mail->assertSeeInText(Carbon::parse($dueDate)->format('d/m/Y'));
        } else {
            $mail->assertDontSeeInHtml('Fecha recomendada:');
            $mail->assertDontSeeInText('Fecha recomendada:');
        }
    }

    public function test_manual_delivery_allows_mileage_only_and_overdue_orders(): void
    {
        Mail::fake();
        $this->actingAs($this->admin);

        foreach ([null, '2026-10-23'] as $date) {
            $this->post(route('reminders.send', $this->order(['next_due_date' => $date])))
                ->assertSessionHas('success');
        }

        Mail::assertSentCount(2);
    }

    public function test_email_escapes_customer_data_and_does_not_leak_internal_notes(): void
    {
        $this->customer->update(['full_name' => '<script>alert(1)</script>']);
        $order = $this->order(['technical_notes' => 'Internal secret'])->load(['company', 'customer', 'vehicle']);

        $mail = new MaintenanceReminderMail($order);

        $mail->assertSeeInHtml('<script>alert(1)</script>')
            ->assertDontSeeInHtml('<script>', false)->assertDontSeeInHtml('Internal secret')
            ->assertDontSeeInText('Internal secret');
    }

    public function test_scheduler_runs_at_eight_in_caracas(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command ?? '', 'maintenance:send-reminders'));
        $this->assertNotNull($event);

        $this->travelTo(Carbon::parse('2026-10-24 11:59:00', 'UTC'));
        $this->assertFalse($event->isDue($this->app));
        $this->travelTo(Carbon::parse('2026-10-24 12:00:00', 'UTC'));
        $this->assertTrue($event->isDue($this->app));
        $this->travelTo(Carbon::parse('2026-10-24 12:01:00', 'UTC'));
        $this->assertFalse($event->isDue($this->app));
        $this->assertTrue($event->withoutOverlapping);
    }

    private function order(array $attributes = []): MaintenanceOrder
    {
        return MaintenanceOrder::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'order_number' => 'ORD-202610-0001',
            'service_name_snapshot' => 'Cambio de aceite',
            'service_date' => '2026-07-24',
            'current_mileage' => 10000,
            'next_due_date' => '2026-10-26',
            'next_due_mileage' => 15000,
            'status' => 'completed',
            'origin' => 'workshop',
            ...$attributes,
        ]);
    }
}
