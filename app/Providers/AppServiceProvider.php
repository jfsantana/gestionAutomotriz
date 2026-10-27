<?php

namespace App\Providers;

use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Users\Models\PlatformPolicy;
use App\Domain\Users\Models\UserPolicyAcceptance;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Gate::define('send-maintenance-reminder', fn (User $user, MaintenanceOrder $order): bool => $user->is_active
            && $user->role === 'company_admin'
            && $user->company_id !== null
            && (int) $user->company_id === (int) $order->company_id);

        View::composer(['layouts.app', 'components.sidebar'], function ($view) {
            $policy = PlatformPolicy::activePolicy();
            $acceptance = null;
            $globalCompanySubscription = null;
            $globalCompanyCanOperate = false;

            if (auth()->check() && $policy) {
                $acceptance = UserPolicyAcceptance::where('user_id', auth()->id())
                    ->where('platform_policy_id', $policy->id)
                    ->first();
            }

            $user = auth()->user();
            if ($user && in_array($user->role, ['company_admin', 'mechanic'], true) && $user->company) {
                $globalCompanySubscription = $user->company->currentSubscription;
                $globalCompanyCanOperate = $globalCompanySubscription
                    ? $globalCompanySubscription->isOperable()
                    : false;
            }

            $view->with('currentPlatformPolicy', $policy);
            $view->with('userPolicyAcceptance', $acceptance);
            $view->with('globalCompanySubscription', $globalCompanySubscription);
            $view->with('globalCompanyCanOperate', $globalCompanyCanOperate);
        });
    }
}
