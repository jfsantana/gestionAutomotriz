<?php

namespace App\Console\Commands;

use App\Domain\Billing\Models\CompanySubscription;
use App\Domain\Billing\Models\CompanySubscriptionLog;
use App\Domain\Companies\Models\Company;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:process-expired';
    protected $description = 'Detecta suscripciones trial o active cuya fecha ends_at haya vencido y las marca como expired.';

    public function handle(): int
    {
        $this->info('Verificando suscripciones vencidas...');

        $expiredSubscriptions = CompanySubscription::query()
            ->whereIn('status', ['trial', 'active'])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', Carbon::today())
            ->get();

        $count = 0;
        foreach ($expiredSubscriptions as $sub) {
            $prev = $sub->status;
            $sub->update(['status' => 'expired']);

            Company::where('id', $sub->company_id)->update(['status' => 'expired']);

            CompanySubscriptionLog::create([
                'company_subscription_id' => $sub->id,
                'company_id' => $sub->company_id,
                'previous_status' => $prev,
                'new_status' => 'expired',
                'action' => 'expired',
                'notes' => 'Vencimiento automático ejecutado por el sistema.',
            ]);

            $count++;
        }

        $this->info("Se marcaron {$count} suscripciones como vencidas (expired).");

        return Command::SUCCESS;
    }
}
