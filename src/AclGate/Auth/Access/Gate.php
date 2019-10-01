<?php

namespace Gecche\Breeze\Auth\Access;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Illuminate\Auth\Access\Gate as GateLaravel;

class Gate extends GateLaravel
{


    /**
     * @var null|\Closure
     */
    protected $aclNone = null;

    /**
     * @var null|\Closure
     */
    protected $aclAll = null;

    /**
     * @param \Closure|null $aclNone
     */
    public function setAclNone($aclNone)
    {
        $this->aclNone = $aclNone;
    }

    /**
     * @param \Closure|null $aclAll
     */
    public function setAclAll($aclAll)
    {
        $this->aclAll = $aclAll;
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
            return $this->buildAclNone($builder,$modelClass);
        }

        $arguments = Arr::wrap($arguments);

        // First we will call the "before" callbacks for the Gate. If any of these give
        // back a non-null response, we will immediately return that result in order
        // to let the developers override all checks for some authorization cases.
        $result = $this->callBeforeCallbacks(
            $user, $ability, $arguments
        );


        if ($result === true) {
            return $this->buildAclAll($builder,$modelClass);
        }
        if ($result === false) {
            return $this->buildAclNone($builder,$modelClass);
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
    protected function buildAclNone($builder) {

        if (!is_null($this->aclNone)) {
            $aclNoneFunc = $this->aclNone;
            return $aclNoneFunc($builder);
        }

        return $builder->where($builder->getKeyName(),-1);
    }

    /**
     * @param Builder $builder
     * @return mixed
     */
    protected function buildAclAll($builder) {

        if (!is_null($this->aclAll)) {
            $aclAllFunc = $this->aclAll;
            return $aclAllFunc($builder);
        }

        return $builder;
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
            return $this->buildAclNone($builder);
        };
    }

}
