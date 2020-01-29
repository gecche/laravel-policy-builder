<?php

namespace Gecche\PolicyBuilder;


use Gecche\PolicyBuilder\Auth\Access\PolicyBuilder;
use Gecche\PolicyBuilder\Contracts\PolicyBuilder as PolicyBuilderContract;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class PolicyBuilderServiceProvider extends ServiceProvider
{


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton(PolicyBuilderContract::class, function ($app) {
            return new PolicyBuilder($app, function () use ($app) {
                return call_user_func($app['auth']->userResolver());
            }, $app[\Illuminate\Contracts\Auth\Access\Gate::class]);
        });

    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {

        Builder::macro('acl', function ($user = null,  $context = null, $arguments = []) {

            $model = $this->model;

            if (is_null($user)) {
                $user = Auth::user();
            }

            return app(PolicyBuilderContract::class)->forUser($user)->acl(get_class($model), $this, $context, $arguments);
        });


    }

}
