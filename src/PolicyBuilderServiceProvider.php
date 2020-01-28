<?php

namespace Gecche\PolicyBuilder;

use Gecche\PolicyBuilder\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
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

        $this->app->singleton(GateContract::class, function ($app) {
            return new Gate($app, function () use ($app) {
                return call_user_func($app['auth']->userResolver());
            });
        });

    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {

        Builder::macro('acl', function ($listType = null, $user = null,  $arguments = []) {

            $model = $this->model;

            if (is_null($user)) {
                $user = Auth::user();
            }

            return app(GateContract::class)->forUser($user)->acl(get_class($model), $this, $listType, $arguments);
        });

//        $this->loadRoutesFrom(__DIR__.'/routes/Test.php');
//        $this->loadViewsFrom(__DIR__.'/../resources/views', 'test');


    }

}
