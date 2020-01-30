<?php namespace Gecche\PolicyBuilder\Tests;

use Gecche\PolicyBuilder\Tests\Models\Author;
use Gecche\PolicyBuilder\Tests\Models\Book;
use Gecche\PolicyBuilder\Tests\Policies\AuthorPolicy;
use Gecche\PolicyBuilder\Tests\Policies\BookPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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
        Author::class => AuthorPolicy::class,
        Book::class => BookPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        /*
         * - It returns the empty list for guest user in "editing" context
         * - For user 1 (superuser) it returns the full list of models for any model and context
         * - For all the other registerd users, it returns the full list of models for Book
         */
        PolicyBuilder::beforeAcl(function ($user, $modelClassName, $context, $builder) {

            if ($context == 'editing' && !$user) {
                return PolicyBuilder::none($builder,$modelClassName);
            }

            if (!$user) {
                return;
            }

            if ($user->getKey() == 1 || $modelClassName == Book::class) {
                return PolicyBuilder::all($builder,$modelClassName);
            }

            return;
        });


    }

}
