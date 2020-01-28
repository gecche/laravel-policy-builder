<?php namespace Gecche\PolicyBuilder\Tests;

use Gecche\PolicyBuilder\Tests\Policies\CodePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use Gecche\PolicyBuilder\Tests\Models\Code;

use Illuminate\Support\Facades\Gate as GateFacade;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
        Code::class => CodePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Implicitly grant "Admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        GateFacade::beforeAcl(function ($user, $modelClassName, $listType) {
            return $user->getKey() == 5 ? true : null;
        });


    }

}
