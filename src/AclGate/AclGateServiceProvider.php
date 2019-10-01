<?php

namespace Gecche\AclGate;

use App\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AclGateServiceProvider extends ServiceProvider
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

        Builder::macro('acl', function ($user = null, $ability = 'acl') {

            $model = $this->model;
            $arguments = [get_class($model), $this];

            if (is_null($user)) {
                $user = Auth::user();
            }

            return app(GateContract::class)->forUser($user)->acl($ability, $arguments);
        });

    }

}
