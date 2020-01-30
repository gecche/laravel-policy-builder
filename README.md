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

### Installation

Add gecche/laravel-policy-builder as a requirement to composer.json:

```javascript
{
    "require": {
        "gecche/laravel-policy-builder": "1.1.*"
    }
}
```

This package makes use of the discovery feature.

### Simple usage

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


#### Get the allowed list for an user

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


### Beyond the basics

The package provides the `PolicyBuilderServiceProvider` which wraps the standard Laravel's Gate class 
and makes available methods for handling Eloquent Builders and returning filtered lists of allowed models.
Also the PolicyBuilder facade is provided. 

#### Changing the "context" of the list

Usually, given a model and an user, the allowed list of models is built always in the same way within an app.
 But let us suppose we want a second list of models for a given user if the context changes. For example, 
 a standard list of models which an user can access and a second list of models which the same user 
   can access with editing privileges.
   
   In that case, you can pass the "context" as the second argument in the builder method as follows:
   

```php
    //returning the 'editing' list for the authenticated user 
    Code::acl(null,'editing')->get();
    //or returning the 'editing' list for user 2 
    $userForAcl = User::find(2);
    Code::acl($userForAcl,'editing')->get();
```

In the Code policy you have to define accordingly the  `aclEditing` method as done before.

#### Basic default builder methods: `all` and `none` 

The PolicyBuilder has two public methods, namely `all` and `none` which basically, given an Eloquent Builder, 
add to it the filters for building an acl list when either all or none of the models are allowed.

Basically, the `all` method returns the builder itself with no added filters while the `none` method adds a filter for 
returning an empty collection of models.

The return of above methods can be customized by using 
the `setAllBuilder` and `setNoneBuilder` methods.

In the following example we change the previous `CodePolicy` class by using default builder methods and leaving 
the semantic as before.

```php

use Illuminate\Support\Facades\Gate;

class CodePolicy
{
    use HandlesAuthorization;

    /**
    /*
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @return mixed
     */
    public function acl($user, $builder)
    {
        if (is_null($user)) {
            return PolicyBuilder::none($builder);
        }
        
        switch ($user->getKey()) {
            case 1:
                return PolicyBuilder::all($builder);
            case 2:
                return $builder->where('code','like','00%');
            case 3:
                return $builder->whereNull('description');
            default:
                return PolicyBuilder::none($builder);

        }

    }
}
```

#### `beforeAcl` Gate method
 
Laravel's Gate has a `before` method for registering "before" callbacks to be processed 
 before ability or policy methods. 
 In the same way the PolicyBuilder has a `beforeAcl` method for registering
  "beforeAcl" callbacks.
  Whereas the "before" callbacks must return either a boolean value or null, the "beforeAcl" callbacks must 
  return either an Eloquent Builder or null.
    
#### `beforeAcl` Policy method

Like the above `beforeAcl` method, also into single policies a `beforeAcl` method could be defined.
    
#### Default return value for `acl` builder method
 
What happens if either no Policy class has been defined for a model or the Policy class has not the `acl` method? 
The `acl` builder method, simply acts as the `none` builder method unless some "beforeAcl" callback applies.
 
For example, if no `CodePolicy` class has been defined and no "beforeAcl" callbacks have been registered, 
the following code returns an empty collection of models:

```php
    Code::acl()->get();
```
  
    



