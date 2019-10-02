<?php
/**
 * Created by PhpStorm.
 * User: gecche
 * Date: 01/10/2019
 * Time: 11:15
 */

namespace Gecche\AclGate\Tests;

use App\Providers\AuthServiceProvider;
use Gecche\AclTest\Tests\Models\User;
use Gecche\AclGate\AclGateServiceProvider as ServiceProvider;
use Gecche\AclTest\Tests\Models\Code;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AclTestCase extends \Orchestra\Testbench\TestCase
{

    use RefreshDatabase;
    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->withFactories(
            __DIR__ . '/database/factories'
        );
        app()->bind(AuthServiceProvider::class, function($app) { // not a service provider but the target of service provider
            return new \Gecche\AclGate\Tests\AuthServiceProvider($app);
        });

        $this->artisan('migrate', ['--database' => 'testbench']);

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');
        });


        factory(User::class, 10)->create();



    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // set up database configuration
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Get Sluggable package providers.
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
            TestServiceProvider::class
        ];
    }


    public function testBasicTest()
    {


        echo "PROVA " . User::count() . "\n";

        $code = Code::create([
            'code' => '001',
            'description' => 'test1',
        ]);

        $code->acl()->get();

        $this->assertTrue(true);
    }
}