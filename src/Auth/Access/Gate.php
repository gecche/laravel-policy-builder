<?php

namespace Gecche\PolicyBuilder\Auth\Access;

use Gecche\PolicyBuilder\Contracts\PolicyBuilder;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Auth\Access\Gate as GateLaravel;

class Gate extends GateLaravel implements PolicyBuilder
{


    /**
     * @var array
     */
    protected $builderMethods = [];

    /**
     * @var array
     */
    protected $beforeAclCallbacks = [];

    /**
     * Create a new gate instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param  callable  $userResolver
     * @param  array  $abilities
     * @param  array  $policies
     * @param  array  $beforeCallbacks
     * @param  array  $afterCallbacks
     * @param  \Closure|null  $allBuilder
     * @param  \Closure|null  $noneBuilder
     * @return void
     */
    public function __construct(Container $container, callable $userResolver, array $abilities = [],
                                array $policies = [], array $beforeCallbacks = [], array $afterCallbacks = [],
                                array $builderMethods = [], array $beforeAclCallbacks = [])
    {
        parent::__construct($container, $userResolver, $abilities, $policies, $beforeCallbacks, $afterCallbacks);

        $this->builderMethods = $builderMethods;
        $this->beforeAclCallbacks = $beforeAclCallbacks;
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
            $this->builderMethods, $this->beforeAclCallbacks
        );
    }

    /**
     * @param array $builderMethods
     */
    public function setBuilderMethods($builderMethods)
    {
        $this->builderMethods = $builderMethods;
    }


    /**
     * @param \Closure|null $noneBuilder
     */
    public function setNoneBuilder($noneBuilder)
    {
        $this->builderMethods['none'] = $noneBuilder;
    }

    /**
     * @param \Closure|null $allBuilder
     */
    public function setAllBuilder($allBuilder)
    {
        $this->builderMethods['all'] = $allBuilder;
    }

    /**
     * @param \Closure|null $guestBuilder
     */
    public function setGuestBuilder($guestBuilder)
    {
        $this->builderMethods['guest'] = $guestBuilder;
    }

    /**
     * Register a callback to run before all Gate checks.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function beforeAcl(callable $callback)
    {
        $this->beforeAclCallbacks[] = $callback;

        return $this;
    }

    /**
     * Call all of the before callbacks and return if a result is given.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $listType
     * @param  string  $modelClassName
     * @param  array  $arguments
     * @return bool|null
     */
    protected function callBeforeAclCallbacks($user, $modelClassName, $listType, $builder, array $arguments)
    {
        $arguments = array_merge([$user, $modelClassName, $listType, $builder], [$arguments]);

        foreach ($this->beforeAclCallbacks as $before) {
            if (! is_null($result = $before(...$arguments))) {
                return $result;
            }
        }
    }

    /**
     * @param Builder $builder
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     */
    public function acl($modelClassName, $builder = null, $listType = null, $arguments = []) {


        if (!$builder) {
            $builder = new $modelClassName;
            $arguments[1] = $builder;
        }

        if (! $user = $this->resolveUser()) {
            return $this->buildBuilderMethod('guest', $builder);
        }

        $arguments = Arr::wrap($arguments);

        // First we will call the "before" callbacks for the Gate. If any of these give
        // back a non-null response, we will immediately return that result in order
        // to let the developers override all checks for some authorization cases.
        $result = $this->callBeforeAclCallbacks(
            $user, $modelClassName, $listType, $builder, $arguments
        );

        if (is_null($result)) {
            $result = $this->callAclCallback($user, $modelClassName, $builder, $listType, $arguments);
        }

        return $result;

    }


    /**
     * @param Builder $builder
     * @return mixed
     */
    protected function buildBuilderMethod($type,$builder) {

        if (!in_array($type,['all','none','guest'])) {
            throw new InvalidArgumentException('acl method type not allowed: it must be "all", "none" or "guest"');
        }

        $aclType = Arr::get($this->builderMethods,$type);

        if (!is_null($aclType)) {
            $aclTypeFunc = $aclType;
            return $aclTypeFunc($builder);
        }

        switch ($type) {
            case 'all':
                return $builder;
            case 'none':
                return $builder->whereRaw(0);
            case 'guest':
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
    protected function callAclCallback($user, $modelClassName, $builder, $listType, array $arguments)
    {
        $callback = $this->resolveAclCallback($user, $modelClassName, $builder, $listType, $arguments);

        return $callback($user, $builder, ...$arguments);
    }


    /**
     * Resolve the callable for the given ability and arguments.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return callable
     */
    protected function resolveAclCallback($user, $modelClassName, $builder, $listType, array $arguments)
    {
        if (!is_null($policy = $this->getPolicyFor($modelClassName)) &&
            $callback = $this->resolvePolicyAclCallback($user, $builder, $listType, $arguments, $policy)) {
            return $callback;
        }

        return function () use ($builder) {
            return $this->none($builder);
        };
    }

    /**
     * Resolve the callback for a policy check.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @param  mixed  $policy
     * @return bool|callable
     */
    protected function resolvePolicyAclCallback($user, $builder, $listType, array $arguments, $policy)
    {
//        if (! is_callable([$policy, $this->formatListTypeToAclMethod($listType)])) {
//            return false;
//        }

        return function () use ($user, $listType, $builder, $arguments, $policy) {

            $result = $this->callPolicyBeforeAcl(
                $policy, $user, $listType, $builder, $arguments
            );

            // When we receive a non-null result from this before method, we will return it
            // as the "final" results. This will allow developers to override the checks
            // in this policy to return the result for all rules defined in the class.
            if (! is_null($result)) {
                return $result;
            }

            $aclMethod = $this->formatListTypeToAclMethod($listType);

            return is_callable([$policy, $aclMethod])
                ? $policy->{$aclMethod}($user, $builder, ...$arguments)
                : false;
        };
    }


    /**
     * Call the "before" method on the given policy, if applicable.
     *
     * @param  mixed  $policy
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $ability
     * @param  array  $arguments
     * @return mixed
     */
    protected function callPolicyBeforeAcl($policy, $user, $listType, $builder, $arguments)
    {
        if (method_exists($policy, 'beforeAcl')) {
            return $policy->beforeAcl($user, $listType, $builder, ...$arguments);
        }
    }

    /**
     * Format the policy ability into a method name.
     *
     * @param  string  $ability
     * @return string
     */
    protected function formatListTypeToAclMethod($listType = null)
    {
        return 'acl'. ($listType ?: Str::camel($listType));
    }


    public function all($builder) {
        return $this->buildBuilderMethod('all', $builder);
    }

    public function none($builder) {
        return $this->buildBuilderMethod('none', $builder);
    }

    public function guest($builder) {
        return $this->buildBuilderMethod('guest', $builder);
    }
}
