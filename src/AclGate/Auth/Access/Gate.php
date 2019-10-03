<?php

namespace Gecche\AclGate\Auth\Access;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Auth\Access\Gate as GateLaravel;

class Gate extends GateLaravel
{


    /**
     * @var array
     */
    protected $aclMethods = [];

    /**
     * Create a new gate instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  callable  $userResolver
     * @param  array  $abilities
     * @param  array  $policies
     * @param  array  $beforeCallbacks
     * @param  array  $afterCallbacks
     * @param  \Closure|null  $aclAll
     * @param  \Closure|null  $aclNone
     * @return void
     */
    public function __construct(Container $container, callable $userResolver, array $abilities = [],
                                array $policies = [], array $beforeCallbacks = [], array $afterCallbacks = [],
                                array $aclMethods = [])
    {
        parent::__construct($container, $userResolver, $abilities, $policies, $beforeCallbacks, $afterCallbacks);

        $this->aclMethods = $aclMethods;
    }

    /**
     * Get a gate instance for the given user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @return static
     */
    public function forUser($user)
    {
        $callback = function () use ($user) {
            return $user;
        };

        return new static(
            $this->container, $callback, $this->abilities,
            $this->policies, $this->beforeCallbacks, $this->afterCallbacks,
            $this->aclMethods
        );
    }

    /**
     * @param array $aclMethods
     */
    public function setAclMethods($aclMethods)
    {
        $this->aclMethods = $aclMethods;
    }

    /**
     * @param \Closure|null $aclNone
     */
    public function setAclNone($aclNone)
    {
        $this->aclMethods['none'] = $aclNone;
    }

    /**
     * @param \Closure|null $aclAll
     */
    public function setAclAll($aclAll)
    {
        $this->aclMethods['all'] = $aclAll;
    }

    /**
     * @param \Closure|null $aclAll
     */
    public function setAclGuest($aclGuest)
    {
        $this->aclMethods['guest'] = $aclGuest;
    }


    /**
     * @param Builder $builder
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     */
    public function acl($ability, $arguments = []) {


        $modelClass = Arr::get($arguments,0);
        $builder = Arr::get($arguments,1);

        if (!$modelClass) {
            throw new InvalidArgumentException($modelClass . ' not defined');
        }
        if (!$builder) {
            $builder = new $modelClass;
            $arguments[1] = $builder;
        }

        if (! $user = $this->resolveUser()) {
            return $this->buildAclMethod('guest', $builder);
        }

        $arguments = Arr::wrap($arguments);

        // First we will call the "before" callbacks for the Gate. If any of these give
        // back a non-null response, we will immediately return that result in order
        // to let the developers override all checks for some authorization cases.
        $result = $this->callBeforeCallbacks(
            $user, $ability, $arguments
        );


        if ($result === true) {
            $result = $this->buildAclMethod('all', $builder);
        }
        if ($result === false) {
            $result = $this->buildAclMethod('none', $builder);
        }

        if (is_null($result)) {
            $result = $this->callAclCallback($user, $ability, $arguments);
        }

        // After calling the authorization callback, we will call the "after" callbacks
        // that are registered with the Gate, which allows a developer to do logging
        // if that is required for this application. Then we'll return the result.
        $this->callAfterCallbacks(
            $user, $ability, $arguments, $result
        );

        return $result;

    }


    /**
     * @param Builder $builder
     * @return mixed
     */
    protected function buildAclMethod($type,$builder) {

        if (!in_array($type,['all','none','guest'])) {
            throw new InvalidArgumentException('acl method type not allowed: it must be "all", "none" or "guest"');
        }

        $aclType = Arr::get($this->aclMethods,$type);

        if (!is_null($aclType)) {
            $aclTypeFunc = $aclType;
            return $aclTypeFunc($builder);
        }

        switch ($type) {
            case 'all':
                return $builder;
            case 'none':
                return $builder->whereRaw(0);
            default:
                return $builder->whereRaw(0);
        }
    }


    /**
     * Resolve and call the appropriate authorization callback.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return bool
     */
    protected function callAclCallback($user, $ability, array $arguments)
    {
        $callback = $this->resolveAclCallback($user, $ability, $arguments);

        return $callback($user, ...$arguments);
    }


    /**
     * Resolve the callable for the given ability and arguments.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return callable
     */
    protected function resolveAclCallback($user, $ability, array $arguments)
    {
        $builder = Arr::get($arguments,1);

        if (isset($arguments[0]) &&
            ! is_null($policy = $this->getPolicyFor($arguments[0])) &&
            $callback = $this->resolvePolicyCallback($user, $ability, $arguments, $policy)) {
            return $callback;
        }

        if (isset($this->abilities[$ability])) {
            return $this->abilities[$ability];
        }

        return function () use ($builder) {
            return $this->buildAclMethod('none',$builder);
        };
    }

}
