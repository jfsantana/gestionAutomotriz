<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
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

        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $policy = \App\Domain\Users\Models\PlatformPolicy::activePolicy();
            $acceptance = null;

            if (auth()->check() && $policy) {
                $acceptance = \App\Domain\Users\Models\UserPolicyAcceptance::where('user_id', auth()->id())
                    ->where('platform_policy_id', $policy->id)
                    ->first();
            }

            $view->with('currentPlatformPolicy', $policy);
            $view->with('userPolicyAcceptance', $acceptance);
        });
    }
}

