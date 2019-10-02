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
use Illuminate\Support\Facades\Auth;

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
            __DIR__ . '/../database/factories'
        );
//        app()->bind(AuthServiceProvider::class, function($app) { // not a service provider but the target of service provider
//            return new \Gecche\AclGate\Tests\AuthServiceProvider($app);
//        });

        $this->artisan('migrate', ['--database' => 'testbench']);

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');
        });


        factory(User::class, 10)->create();

        Code::create([
            'code' => '001',
            'description' => 'test1',
        ]);

        Code::create([
            'code' => '010',
            'description' => null,
        ]);

        Code::create([
            'code' => '002',
            'description' => null,
        ]);

        Code::create([
            'code' => '012',
            'description' => 'test2',
        ]);
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
        $app['config']->set('auth.providers', [
            'users' => [
                'driver' => 'eloquent',
                'model' => User::class,
            ]
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
            TestServiceProvider::class,
            \Gecche\AclGate\Tests\AuthServiceProvider::class
        ];
    }


    /*
     * The code policy in these tests simply allows for:
     * - all the codes to user 1
     * - all the codes with code starting with "00" to user 2
     * - all the codes with a null description to user 3
     * - only code with id 1 to all other users
     * - no codes for guests
     *
     */


    /*
     * Test authentication with user 1
     */
    public function testAuthUser1()
    {


        $user = Auth::loginUsingId(1);

        $this->assertAuthenticated();

        $this->assertAuthenticatedAs($user);

        $codes = Code::acl()->get()->toArray();


        $this->assertEquals(count($codes),4);
    }

    /*
     * Test authentication with user 2
     */
    public function testAuthUser2()
    {


        $user = Auth::loginUsingId(2);

        $this->assertAuthenticated();

        $this->assertAuthenticatedAs($user);

        $codes = Code::acl()->get()->pluck('code','id')->toArray();

        $this->assertEquals(count($codes),2);

        $this->assertEquals([1 => '001',3 => '002'],$codes);

    }

    /*
    * Test authentication with user 3
    */
    public function testAuthUser3()
    {


        $user = Auth::loginUsingId(3);

        $this->assertAuthenticated();

        $this->assertAuthenticatedAs($user);

        $codes = Code::acl()->get()->pluck('code','id')->toArray();

        $this->assertEquals(count($codes),2);

        $this->assertEquals([2 => '010',3 => '002'],$codes);

    }

    /*
    * Test guest user
    */
    public function testGuestUser()
    {


        $this->assertGuest();

        $codes = Code::acl()->get()->pluck('code','id')->toArray();

        $this->assertEquals(count($codes),0);
        
    }
}