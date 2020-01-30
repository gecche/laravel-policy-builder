[![Laravel](https://img.shields.io/badge/Laravel-5.x-orange.svg?style=flat-square)](http://laravel.com)
[![Laravel](https://img.shields.io/badge/Laravel-6.x-orange.svg?style=flat-square)](http://laravel.com)
[![License](http://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://tldrlegal.com/license/mit-license)

# laravel-policy-builder
A simple and convenient way to build allowed list of Eloquent models according to policies.

## Description
  In many apps you use Laravel's Policies for checking if an user is allowed to handle a resource. 
  Usually, in those apps, you also have to get lists of allowed resources accordingly to policies.
  
  By using this package you store the business logic of filtering lists of resources directly in the 
  policies and you get such lists by simply calling the method `acl` when using an Eloquent Builder.

## Documentation

### Version Compatibility

 Laravel  | PolicyBuilder
:---------|:----------
 5.5.x    | 1.1.x
 5.6.x    | 1.2.x
 5.7.x    | 1.3.x

### Installation

Add gecche/laravel-policy-builder as a requirement to composer.json:

```javascript
{
    "require": {
        "gecche/laravel-policy-builder": "1.2.*"
    }
}
```

This package makes use of the discovery feature.

### Basic usage

#### Define the business logic of building allowed lists of models in the policies
Let us suppose to have an `Author` Model class and a standard `AuthorPolicy` class for defining ability methods as 
usual.

Simply add directly in the `AuthorPolicy` class the business logic for filtering lists of Author. E.g.:

```php
class AuthorPolicy
{
    use HandlesAuthorization;

    /**
    /*
     * - All authors are allowed to users 1 and 2
     * - Only italian authors are allowed to users 3 and 4
     * - Only non-italian authors are allowed to other users
     *
     * @param   \Illuminate\Contracts\Auth\Authenticatable|null $user
     * @param  Builder $builder
     * @return mixed
     */
    public function acl($user, $builder)
    {
    
        switch ($user->getKey()) {
            case 1:
            case 2:
                return $builder;
            case 3:
            case 4:
                return $builder->where('nation','IT');
            default:
                return $builder->where('nation','<>','IT');

        }


    }
```


#### Get the allowed list of models for an user

Now, to get the allowed list of authors for the currently authenticated user, simply do:

```php
    Author::acl()->get();
```

If you want the list for the user 3, simply do:

```php
    $userForAcl = User::find(3);
    Code::acl($userForAcl)->get();
```

Now the lists returns only italian authors.

#### Default list

Let us consider another `Book` model for which either the `acl` method has not been defined in 
its `BookPolicy` or there is no `BookPolicy` at all.

If we do:

```php
    Book::acl()->get();
```

we get the empty list of models for any user.


### Beyond the basics

Once installed, other than the `acl` Eloquent Builder macro, 
the package provides the `PolicyBuilderServiceProvider` (together with the 
`PolicyBuilder` facade) which performs the underlying machinery for linking 
the Eloquent Builder with the policies 
(by wrapping the Laravel's `Gate` provider) and it offers some useful methods.

#### Basic default builder methods: `all` and `none` 

The PolicyBuilder has two public methods, namely `all` and `none` which basically, 
given an Eloquent Builder adn (optionally) the model class name, return respectively 
the list of all available models (no filters at all) and the empty list.

The return of above methods can be customized by using the `setAllBuilder` and `setNoneBuilder` methods.

In the following example we change the previous `AuthorPolicy` class with the  
PolicyBuilder's `all` method, but we leave the same semantics as before.

```php
use Gecche\PolicyBuilder\Facades\PolicyBuilder;
use App\Models\Author;

class AuthorPolicy
{
    use HandlesAuthorization;

    /**
    /*
     * - All authors are allowed to users 1 and 2
     * - Only italian authors are allowed to users 3 and 4
     * - Only non-italian authors are allowed to other users
     *
     * @param   \Illuminate\Contracts\Auth\Authenticatable|null $user
     * @param  Builder $builder
     * @return mixed
     */
    public function acl($user, $builder)
    {
    
        switch ($user->getKey()) {
            case 1:
            case 2:
                return PolicyBuilder::all($builder,Author::class);
            case 3:
            case 4:
                return $builder->where('nation','IT');
            default:
                return $builder->where('nation','<>','IT');

        }
    }
```
    
As before for both user 1 and 2 the full list of authors is returned if we do:

```php
    Author::acl()->get();
```

However we can set globally a different semantics for the PolicyBuilder's `all` method, e.g.: 

```php
PolicyBuilder::setAllBuilder(function ($builder,$modelClassName = null) {
    if ($modelClassName == Author::class) {
        return $builder->where('id','<>',1);
    }
    return $builder;
});
```

In the above example when the `all` method is called the list of authors lacks 
the author with id 1.

The same can be done with the PolicyBuilder's `none` method.


#### Changing the "context" 

Usually, an user either can access or not a certain model. 
But there are some cases in which, under certain "context", we need to built 
a list of allowed models which is different than the standard one.

For example, an user can view the whole list of `Author` models in the library, 
but it cannot edit all of them. 
So we want to build also the list of books which the user can edit 
and we are changing to the `editing` "context" with a different business 
logic for building the list.
   
In that case, simply pass the "context" to the builder:
   
```php
    //returning the 'editing' list for the authenticated user 
    Author::acl(null,'editing')->get();
    //or returning the 'editing' list for user 2 
    $userForAcl = User::find(2);
    Author::acl($userForAcl,'editing')->get();
```

In the AuthorPolicy you have to define accordingly the  `aclEditing` method as done before for the `acl` one.


#### `beforeAcl` PolicyBuilder and Policy methods
 
Like the Laravel's Gate `before` method, PolicyBuilder has a `beforeAcl` method for registering
  "beforeAcl" callbacks. If a registered callback returns an Eloquent Builder, further elaboration 
  is not needed and thus no policy is needed at all. E.g.:
  
```php
/*
 * - For user 1 (superuser) it returns the full list of models for any model and context
 * - For all the other registerd users, it returns the full list of models for Book
 */
PolicyBuilder::beforeAcl(function ($user, $modelClassName, $context, $builder) {

    if (!$user) {
        return;
    }

    if ($user->getKey() == 1 || $modelClassName == Book::class) {
        return PolicyBuilder::all($builder,$modelClassName);
    }

    return;
});  
```

A very similar `beforeAcl` method can also be placed into a single policy and 
it will be handled by the `PolicyBuilderServiceProvider` before elaborating 
any other method in the policy. 

```php
use Gecche\PolicyBuilder\Facades\PolicyBuilder;
use App\Models\Author;

class AuthorPolicy
{
    use HandlesAuthorization;


    public function beforeAcl($user, $context, $builder) {

        if (is_null($user)) {
            return PolicyBuilder::none($builder,Author::class);
        }

        return null;

    }

    ...
    
```php

In the above example, the guest user has no access at all to the authors.



