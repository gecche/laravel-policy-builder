[![Laravel](https://img.shields.io/badge/Laravel-5.x-orange.svg?style=flat-square)](http://laravel.com)
[![Laravel](https://img.shields.io/badge/Laravel-6.x-orange.svg?style=flat-square)](http://laravel.com)
[![License](http://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://tldrlegal.com/license/mit-license)

# laravel-policy-builder
A simple and convenient way to build allowed list of Eloquent models according to policies.

## Description
Laravel's Gate and Policies are very useful tools if you want to check if a user is allowed to perform an action like 
 viewing, creating or editing a resource. 
  However, in many apps you have to create lists of resources which are allowed to be accessed by an user 
  accordingly to policies (Eloquent Access Control Lists).
  This package adds a method to an Eloquent Builder for creating Eloquent ACL, storing the business logic 
  directly in the policies.

## Documentation

### Version Compatibility

 Laravel  | PolicyBuilder
:---------|:----------
 5.5.x    | 1.1.x
 5.6.x    | 1.2.x

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

### Simple usage

#### Define the business logic of Eloquent ACL in the policies
Let us suppose to have a `Code` Model class with `id`, `code` and `description` fields together with a standard 
`CodePolicy` class in which we can define ability methods as usual.

Simply add in the `CodePolicy` class, the business logic for allowed lists of Code models in the `acl` method.

```php
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
            return $builder->where('id',-1);
        }
        
        switch ($user->getKey()) {
            case 1:
                return $builder;
            case 2:
                return $builder->where('code','like','00%');
            case 3:
                return $builder->whereNull('description');
            default:
                return $builder->where('id',-1);

        }

    }
}
```

In this example, User 1 has access to all the codes, User 2 has access to all the codes with `code` starting with `00`, 
User 3 has access to all codes with a null `description` and finally both the guest user (null) and all the others 
registered users have no access to any code.

#### Get the allowed list for an user

Now, to get the Access Control Lists for the `Code` Model, simply do:

```php
    Code::acl()->get();
```

The returned list is filtered for the currently authenticated user.

To get the lists for, namely, user 2, simply do:

```php
    $userForAcl = User::find(2);
    Code::acl($userForAcl)->get();
```

Now the lists is built with respect to the `Code` models allowed to user 2.

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


### Beyond the basics

The package provides the `PolicyBuilderServiceProvider` which wraps the standard Laravel's Gate class 
and makes available methods for handling Eloquent Builders and returning filtered lists of allowed models.
Also the PolicyBuilder facade is provided. 

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
  
    



