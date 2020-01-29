<?php
/**
 * Created by PhpStorm.
 * User: gecche
 * Date: 01/10/2019
 * Time: 11:15
 */

namespace Gecche\PolicyBuilder\Tests;

use App\Providers\AuthServiceProvider;
use Gecche\PolicyBuilder\Tests\Models\User;
use Gecche\PolicyBuilder\PolicyBuilderServiceProvider as ServiceProvider;
use Gecche\PolicyBuilder\Tests\Models\Code;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Gecche\PolicyBuilder\Facades\PolicyBuilder;

class PolicyBuilderTestCase extends \Orchestra\Testbench\TestCase
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
//            return new \Gecche\PolicyBuilder\Tests\AuthServiceProvider($app);
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
            \Gecche\PolicyBuilder\Tests\AuthServiceProvider::class
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
     * Furthermore in \Gecche\PolicyBuilder\Tests\AuthServiceProvider, a before callback is registeres to grant access to
     * user 5, so usign the all method.
     *
     */


    /*
     * Test authentication with user 1
     */
    public function testAuthUser1()
    {


        $user = Auth::loginUsingId(1);

        $this->assertAuthenticatedAs($user);

        $codes = Code::acl()->get()->toArray();


        $this->assertEquals(count($codes), 4);
    }

    /*
     * Test authentication with user 2
     */
    public function testAuthUser2()
    {


        $user = Auth::loginUsingId(2);

        $this->assertAuthenticatedAs($user);

        $codes = Code::acl()->get()->pluck('code', 'id')->toArray();

        $this->assertEquals([1 => '001', 3 => '002'], $codes);

    }

    /*
    * Test authentication with user 3
    */
    public function testAuthUser3()
    {


        $user = Auth::loginUsingId(3);

        $this->assertAuthenticatedAs($user);

        $codes = Code::acl()->get()->pluck('code', 'id')->toArray();

        $this->assertEquals([2 => '010', 3 => '002'], $codes);

    }

    /*
     * Test authentication with user 4
     */
    public function testAuthUser4()
    {


        $user = Auth::loginUsingId(4);

        $this->assertAuthenticatedAs($user);

        $codes = Code::acl()->get()->pluck('code', 'id')->toArray();

        $this->assertEquals([1 => '001'], $codes);

    }

    /*
     * Test authentication with user 4
     */
    public function testAuthUser5()
    {


        $user = Auth::loginUsingId(5);

        $this->assertAuthenticatedAs($user);

        $codes = Code::acl()->get()->pluck('code', 'id')->toArray();

        $this->assertEquals(count($codes), 4);

//        $this->assertEquals([1 => '001'], $codes);

    }


    /*
     * Test authentication with user 1 (list type admin)
     */
    public function testCodeAdminListAuthUser1()
    {


        $user = Auth::loginUsingId(1);

        $this->assertAuthenticatedAs($user);

        $codes = Code::acl(null,'admin')->get()->pluck('code', 'id')->toArray();

        $this->assertEquals(count($codes), 4);

//        $this->assertEquals([1 => '001'], $codes);

    }

    /*
     * Test authentication with user 1 (list type admin)
     */
    public function testCodeAdminListAuthUser2And3()
    {


        $user = Auth::loginUsingId(2);

        $this->assertAuthenticatedAs($user);

        $codes = Code::acl(null,'admin')->get()->pluck('code', 'id')->toArray();

        $this->assertEquals([1 => '001'], $codes);

        $user = Auth::loginUsingId(3);

        $this->assertAuthenticatedAs($user);

        $codes = Code::acl(null,'admin')->get()->pluck('code', 'id')->toArray();

        $this->assertEquals([1 => '001'], $codes);

    }


    /*
 * Test authentication with user 4
 */
    public function testCodeVerypublicListAuthUser1And4()
    {


        $user = Auth::loginUsingId(1);

        $this->assertAuthenticatedAs($user);

        $codes = Code::acl(null,'verypublic')->get()->pluck('code', 'id')->toArray();

        $this->assertEquals(count($codes), 4);

        $user = Auth::loginUsingId(4);

        $this->assertAuthenticatedAs($user);

        $codes = Code::acl(null,'verypublic')->get()->pluck('code', 'id')->toArray();

        $this->assertEquals(count($codes), 4);

    }

    /*
 * Test authentication with user 4
 */
    public function testAuthUser5all()
    {


        $user = Auth::loginUsingId(5);

        $this->assertAuthenticatedAs($user);

        $codes = Code::acl()->get()->pluck('code', 'id')->toArray();

        $this->assertEquals(count($codes), 4);

        PolicyBuilder::setAllBuilder(function ($builder) {
            return $builder->where('id', 4);
        });

        $codes = Code::acl()->get()->pluck('code', 'id')->toArray();

        $this->assertEquals([4 => '012'], $codes);

    }

    /*
     * Test guest user
     */
    public function testGuestUser()
    {


        $this->assertGuest();

        $codes = Code::acl()->get()->pluck('code', 'id')->toArray();

        $this->assertEquals(count($codes), 0);

    }


    /*
     * Test guest with another aclNone function
     */

    public function testGuestUserAclGuest()
    {


        $this->assertGuest();

        $codes = Code::acl()->get()->pluck('code', 'id')->toArray();

        $this->assertEquals(count($codes), 0);

    }


    /*
     * Test user 1 and 5 with User model (no policy defined)
     */

    public function testAuthUsers1UserModel()
    {

        $user = Auth::loginUsingId(1);

        $this->assertAuthenticatedAs($user);

        $users = User::acl()->get()->pluck('name', 'id')->toArray();

        $this->assertEquals([], $users);

        PolicyBuilder::setNoneBuilder(function ($builder) {
            return $builder->where('id', 4);
        });

        $users = User::acl()->get()->pluck('name', 'id')->toArray();
        $this->assertEquals(count($users), 1);

    }

    public function testAuthUsers1UserModelAclNone()
    {

        $user = Auth::loginUsingId(1);

        $this->assertAuthenticatedAs($user);

        $users = User::acl()->get()->pluck('name', 'id')->toArray();

        $this->assertEquals([], $users);



    }

    public function testAuthUsers5UserModel()
    {

        $user = Auth::loginUsingId(5);

        $this->assertAuthenticatedAs($user);

        $users = User::acl()->get()->pluck('name', 'id')->toArray();

        $this->assertEquals(count($users),10);

    }

    /*
     * Test guest with User model (no policy defined)
     */

    public function testGuestUserModel()
    {


        $this->assertGuest();

        $users = User::acl()->get()->pluck('name', 'id')->toArray();

        $this->assertEquals([], $users);

    }
}
