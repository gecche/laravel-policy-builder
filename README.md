[![Laravel](https://img.shields.io/badge/Laravel-5.x-orange.svg?style=flat-square)](http://laravel.com)
[![Laravel](https://img.shields.io/badge/Laravel-6.x-orange.svg?style=flat-square)](http://laravel.com)
[![License](http://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://tldrlegal.com/license/mit-license)

# laravel-acl-gate
A simple and convenient way to build allowed list of eloquent models (acl lists).

## Description
This package adds the `acl` method to Eloquent Models which returns an "acl filtered" builder.

## Documentation

### Version Compatibility

 Laravel  | AclGate
:---------|:----------
 5.5.x    | 1.1.x
 5.6.x    | 1.2.x

### Installation

Add gecche/laravel-acl-gate as a requirement to composer.json:

```javascript
{
    "require": {
        "gecche/laravel-acl-gate": "1.2.*"
    }
}
```

This package makes use of the discovery feature.

### Usage

The package extends the default Laravel Gate by handling methods for returning 
an Eloquent Builder filtered by constraints defined in the standard model policy classes. 

#### Define the constraints in the policy
Let us suppose to have a `Code` Model class with `id`, `code` and `description` fields. 
Above model has associated a `CodePolicy` class defined in the standard way.
In our app, User 1 has access to all the codes, User 2 has access to all the codes with `code` starting with `00`, 
User 3 has access to all codes with a null `description` and finally all the other users have access only to the code 
with `id` 1.

To define the constraints simply add in the `CodePolicy` class the `acl` method as follows:

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
        switch ($user->getKey()) {
            case 1:
                return $builder;
            case 2:
                return $builder->where('code','like','00%');
            case 3:
                return $builder->whereNull('description');
            default:
                return $builder->where('id',1);

        }


    }
}
```
#### Use the `acl` method

Now, to get the acl filtered lists for the `Code` Model, simply do:

```php
    Code::acl()->get();
```

The returned lists is filtered for the corrently authenticated user.

To get the lists for, namely, user 2, simply do:

```php
    $userForAcl = User::find(2);
    Code::acl($userForAcl)->get();
```

Now the lists is built with respect to the `Code` models allowed to user 2.

If you want to use a different method in the Policy class, simply pass its name as the second argument:

```php
    Code::acl(null,'myacl')->get();
    //or for example:
    $userForAcl = User::find(2);
    Code::acl($userForAcl,'myacl')->get();
```

#### Defaults
 
The extended Gate has three defaults results:
 
 - No model is allowed (`AclNone`): it is used by the Gate when
    - No policy for a model has been defined
    - No`acl` method has been define in the policy
    - A Before Gate callback returns `false`
 - All models are allowed (`AclAll`): it is used by the Gate when a Before Gate callback returns `true` 
 - Models for Guest users (`AclGuest`)
 
 It is possible to customize the defaults in a simple way.
 Let us consider `AclNone`. The default is a function returning an empty builder:
 
 ```php
    return $builder->whereRaw(0);
  ```
 To override this behaviour, simply use a `Closure` whenever you want:
 
 ```php
    Gate::setAclNone(function ($builder) {
        return $builder->where('id',-1);
    });
 ```
 
 From now on, the method is registered to the Gate.
 The same applies for `AclAll` and `AclGuest`.
    
 

    



