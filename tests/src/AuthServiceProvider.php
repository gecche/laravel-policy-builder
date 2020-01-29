<?php namespace Gecche\PolicyBuilder\Tests;

use Gecche\PolicyBuilder\Tests\Policies\CodePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use Gecche\PolicyBuilder\Tests\Models\Code;

use Gecche\PolicyBuilder\Facades\PolicyBuilder;

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
        PolicyBuilder::beforeAcl(function ($user, $modelClassName, $context, $builder) {
            return ($user && $user->getKey() == 5) ? PolicyBuilder::all($builder) : null;
        });


    }

}
