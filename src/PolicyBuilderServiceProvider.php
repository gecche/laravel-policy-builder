<?php

namespace Gecche\PolicyBuilder;

use Gecche\PolicyBuilder\Auth\Access\PolicyBuilder;
use Gecche\PolicyBuilder\Contracts\PolicyBuilder as PolicyBuilderContract;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as DBBuilder;
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

        /*
         * The macro builder for adding policy filters.
         * If the $user is false, it acts as for guest user.
         * If the $user is null it tries to instantiate the currently authenticated user
         */
        DBBuilder::macro('acl', function ($modelClass, $user = null,  $context = null, $arguments = []) {

            if ($user === false) {
                $user = null;
            } elseif (is_null($user)) {
                $user = Auth::user();
            }

            return app(PolicyBuilderContract::class)->forUser($user)->acl($modelClass, $this, $context, $arguments);
        });

        /*
         * The macro builder for adding policy filters.
         * If the $user is false, it acts as for guest user.
         * If the $user is null it tries to instantiate the currently authenticated user
         */
        Builder::macro('acl', function ($user = null,  $context = null, $arguments = []) {

            $model = $this->model;

            if ($user === false) {
                $user = null;
            } elseif (is_null($user)) {
                $user = Auth::user();
            }

            return app(PolicyBuilderContract::class)->forUser($user)->acl(get_class($model), $this, $context, $arguments);
        });


    }

}
