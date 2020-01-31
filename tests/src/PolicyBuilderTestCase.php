<?php
/**
 * Created by PhpStorm.
 * User: gecche
 * Date: 01/10/2019
 * Time: 11:15
 */

namespace Gecche\PolicyBuilder\Tests;

use Gecche\PolicyBuilder\Facades\PolicyBuilder;
use Gecche\PolicyBuilder\Tests\Models\Author;
use Gecche\PolicyBuilder\Tests\Models\Book;
use Gecche\PolicyBuilder\Tests\Models\User;
use Gecche\PolicyBuilder\PolicyBuilderServiceProvider as ServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

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

        Author::create([
            'name' => 'Dante',
            'surname' => 'Alighieri',
            'nation' => 'IT',
            'birthdate' => '1265-05-21',
        ]);

        Author::create([
            'name' => 'Joanne Kathleen',
            'surname' => 'Rowling',
            'nation' => 'UK',
            'birthdate' => '1965-07-31',
        ]);

        Author::create([
            'name' => 'Stephen',
            'surname' => 'King',
            'nation' => 'US',
            'birthdate' => '1947-09-21',
        ]);

        Author::create([
            'name' => 'Ken',
            'surname' => 'Follett',
            'nation' => 'UK',
            'birthdate' => '1949-06-05',
        ]);


        Book::create([
            'title' => 'La divina commedia',
            'language' => 'IT',
            'author_id' => 1,
        ]);

        Book::create([
            'title' => 'Fall of giants',
            'language' => 'EN',
            'author_id' => 4,
        ]);

        Book::create([
            'title' => 'The Pillars of the Earth',
            'language' => 'EN',
            'author_id' => 4,
        ]);

        Book::create([
            'title' => 'Misery',
            'language' => 'EN',
            'author_id' => 3,
        ]);

        Book::create([
            'title' => 'Harry Potter and the Philosopher\'s Stone',
            'language' => 'EN',
            'author_id' => 2,
        ]);

        Book::create([
            'title' => 'Harry Potter and the Chamber of Secrets',
            'language' => 'EN',
            'author_id' => 2,
        ]);

        Book::create([
            'title' => 'Harry Potter adn the Prisoner fo Azkaban',
            'language' => 'EN',
            'author_id' => 2,
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
            AuthServiceProvider::class
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
     * In the first 3 tests, we check the PolicyBuilder@beforeAcl method when it returns a Builder.
     * See the logic in \Gecche\PolicyBuilder\Tests\AuthServiceProvider
     *
     */


    /*
     * Test PolicyBuilder's beforeAcl method with user 1
     */
    public function testBeforeAclPolicyBuilderUser1()
    {


        /*
         * Login with user 1
         */
        $user = Auth::loginUsingId(1);

        $this->assertAuthenticatedAs($user);

        /*
         * We expect the full list of Authors and Books
         */
        $authors = Author::acl()->get()->toArray();
        $this->assertEquals(count($authors), 4);

        $books = Book::acl()->get()->toArray();
        $this->assertEquals(count($books), 7);
    }

    /*
     * Test PolicyBuilder's beforeAcl method with guest user
     */
    public function testBeforeAclPolicyBuilderGuest()
    {
        /*
         * No login
         */

        /*
         * We expect the empty list of Authors and Books in "editing" context
         */
        $authors = Author::acl(null,'editing')->get()->toArray();
        $this->assertEquals($authors, []);

        $books = Book::acl(null,'editing')->get()->toArray();
        $this->assertEquals($books, []);
    }

    /*
     * Test PolicyBuilder's beforeAcl method with guest user
     */
    public function testBeforeAclPolicyBuilderUser2()
    {
        /*
         * Login with user 2
         */
        $user = Auth::loginUsingId(2);

        $this->assertAuthenticatedAs($user);

        /*
         * We expect the full list of Books
         */
        $books = Book::acl()->get()->toArray();
        $this->assertEquals(count($books), 7);

    }


    /*
     * From now on, the PolicyBuilder@beforeAcl returns null and so the PolicyBuilder
     * should inspect the models' policies
     */

    /*
     * Test Author's beforeAcl method with guest user
     */
    public function testBeforeAclAuthorPolicyGuest()
    {
        /*
         * No login
         */

        /*
         * We expect the empty list of Authors
         */
        $authors = Author::acl()->get()->toArray();
        $this->assertEquals($authors, []);

    }

    /*
     * Test Author's policy with user 2
     */
    public function testAuthorPolicyUser2()
    {
        /*
         * Login with user 2
         */
        $user = Auth::loginUsingId(2);

        $this->assertAuthenticatedAs($user);


        /*
         * We expect the full list of Authors in standard context
         */
        $authors = Author::acl()->get()->toArray();
        $this->assertEquals(count($authors), 4);

        /*
         * We expect only Dante Alighieri in editing context
         */
        $authors = Author::acl(null,'editing')->get()->pluck('surname', 'id')->toArray();

        $this->assertEquals([1 => 'Alighieri'], $authors);
    }

    /*
     * Test Book's policy with guest user
     */
    public function testBookPolicyGuest()
    {

        /*
         * No Login
         */

        /*
         * We expect the full list of Books
         */
        $books = Book::acl()->get()->pluck('title', 'id')->toArray();

            $arrayExpected = [
                1 => 'La divina commedia',
                2 => 'Fall of giants',
                3 => 'The Pillars of the Earth',
            ];

            $this->assertEquals($arrayExpected, $books);
    }

    /*
     * Test Book's policy with guest user
     */
    public function testForcingUser()
    {

        /*
         * No Login, as the previous test, but now we force to get the list for user 2
         */

        /*
         * We expect the full list of Books
         */
        $userForAcl = User::find(2);
        $books = Book::acl($userForAcl)->get()->pluck('title', 'id')->toArray();

        $this->assertEquals(count($books), 7);


    }

    /*
     * In this test we change the standard PolicyBuilder@all method
     * by setting that in the case of Authors, the author 1 is not returned
     */
    public function testCustomAllBuilderMethod()
    {

        $userforAcl = User::find(1);
        /*
         * We expect the full list of Authors
         */
        $authors = Author::acl($userforAcl)->get()->toArray();
        $this->assertEquals(count($authors), 4);


        /*
         * We set a new logic for PolicyBuilder@all
         */
        PolicyBuilder::setAllBuilder(function ($builder,$modelClassName = null) {
           if ($modelClassName == Author::class) {
               return $builder->where('id','<>',1);
           }
           return $builder;
        });

        /*
         * We expect now the full list of Authors except the author 1
         */
        $authors = Author::acl($userforAcl)->get()->pluck('id','id')->toArray();
        $this->assertEquals(array_values($authors), [2,3,4]);

    }

    /*
     * In this test we change the standard PolicyBuilder@none method
     * by setting that in the case of Books, italian books are always returned
     */
    public function testCustomNoneBuilderMethod()
    {

        /*
         * No login
         */

        /*
         * We expect the empty list of Books
         */
        $books = Book::acl(null,'editing')->get()->toArray();
        $this->assertEquals(count($books), 0);


        /*
         * We set a new logic for PolicyBuilder@all
         */
        PolicyBuilder::setNoneBuilder(function ($builder,$modelClassName = null) {
            if ($modelClassName == Book::class) {
                return $builder->where('language','IT');
            }
            return $builder->whereRaw(0);
        });

        /*
         * We expect now the full list of Authors except the author 1
         */
        $books = Book::acl(null,'editing')->get()->pluck('title', 'id')->toArray();

        $arrayExpected = [
            1 => 'La divina commedia',
        ];

        $this->assertEquals($arrayExpected, $books);

    }

    /*
     * In this test we change both the standard PolicyBuilder@none and
     * PolicyBuilder@all methods via the PolicyBuilder@setBuilderMethods method.
     * The expected values are the same of the last two tests.
     */
    public function testSetBuilderMethodsMethod()
    {

        /*
         * No login
         */

        $userforAcl = User::find(1);
        /*
         * We expect the full list of Authors
         */
        $authors = Author::acl($userforAcl)->get()->toArray();
        $this->assertEquals(count($authors), 4);

        /*
         * We expect the empty list of Books
         */
        $books = Book::acl(null,'editing')->get()->toArray();
        $this->assertEquals(count($books), 0);


        /*
         * We set a new logic for PolicyBuilder@all and PolicyBuilder@none
         */
        $builderMethodsArray = [
          'all' =>  function ($builder,$modelClassName = null) {
              if ($modelClassName == Author::class) {
                  return $builder->where('id','<>',1);
              }
              return $builder;
          },
          'none' => function ($builder,$modelClassName = null) {
              if ($modelClassName == Book::class) {
                  return $builder->where('language','IT');
              }
              return $builder->whereRaw(0);
          }
        ];
        PolicyBuilder::setBuilderMethods($builderMethodsArray);

        /*
         * We expect now the full list of Authors except the author 1
         */
        $authors = Author::acl($userforAcl)->get()->pluck('id','id')->toArray();
        $this->assertEquals(array_values($authors), [2,3,4]);

        /*
         * We expect now the full list of Authors except the author 1
         */
        $books = Book::acl(null,'editing')->get()->pluck('title', 'id')->toArray();

        $arrayExpected = [
            1 => 'La divina commedia',
        ];

        $this->assertEquals($arrayExpected, $books);

    }

    /*
     * In this test we call again the PolicyBuilder@setBuilderMethods method but passing an invalid $type
     * (only 'all' and 'none' are accepted as builder methods).
     */
    public function testSetBuilderMethodsException()
    {

        $this->expectException(\InvalidArgumentException::class);
        /*
         * We set a new logic for PolicyBuilder@all and PolicyBuilder@none
         */
        $builderMethodsArray = [
            'pippo' =>  function ($builder,$modelClassName = null) {
                if ($modelClassName == Author::class) {
                    return $builder->where('id','<>',1);
                }
                return $builder;
            },
            'none' => function ($builder,$modelClassName = null) {
                if ($modelClassName == Book::class) {
                    return $builder->where('language','IT');
                }
                return $builder->whereRaw(0);
            }
        ];
        PolicyBuilder::setBuilderMethods($builderMethodsArray);


    }


    /*
     * In this we test that for model without a policy, the user 1 still gets the
     * full list of models due to the PolicyBuilder@beforeAcl method in AuthServiceProvider,
     * while all the other users gets the empty list of models
     */
    public function testNoExistentUserPolicy()
    {

        /*
         * We expect the full list of Users for user 1
         */
        $userForAcl = User::find(1);
        $users = User::acl($userForAcl)->get()->toArray();

        $this->assertEquals(count($users), 10);

        /*
         * We expect the empty list of Users for all toher users (including guest)
         */
        $userForAcl = User::find(2);
        $users = User::acl($userForAcl)->get()->toArray();

        $this->assertEquals($users, []);

        /*
         * Force the guest user for acl listing
         */
        $users = User::acl(false)->get()->toArray();

        $this->assertEquals($users, []);
    }


}