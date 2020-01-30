<?php

namespace Gecche\PolicyBuilder\Auth\Access;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Gecche\PolicyBuilder\Contracts\PolicyBuilder as PolicyBuilderContract;

class PolicyBuilder implements PolicyBuilderContract
{


    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The user resolver callable.
     *
     * @var callable
     */
    protected $userResolver;

    /**
     * @var array
     */
    protected $builderMethods = [];

    /**
     * The Gate manager.
     *
     * @var Gate
     */
    protected $gate;

    /**
     * @var array
     */
    protected $beforeAclCallbacks = [];


    /**
     * PolicyBuilder constructor.
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @param callable $userResolver
     * @param Gate $gate
     * @param array $builderMethods
     * @param array $beforeAclCallbacks
     */
    public function __construct(Container $container, callable $userResolver, Gate $gate,
                                array $builderMethods = [], array $beforeAclCallbacks = [])
    {

        $this->container = $container;
        $this->userResolver = $userResolver;
        $this->gate = $gate;
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
            $this->container, $callback, $this->gate,
            $this->builderMethods, $this->beforeAclCallbacks
        );
    }

    /**
     * Resolve the user from the user resolver.
     *
     * @return mixed
     */
    protected function resolveUser()
    {
        return call_user_func($this->userResolver);
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
     * @param  string  $context
     * @param  string  $modelClassName
     * @param  array  $arguments
     * @return bool|null
     */
    protected function callBeforeAclCallbacks($user, $modelClassName, $context, $builder, array $arguments)
    {
        $arguments = array_merge([$user, $modelClassName, $context, $builder], [$arguments]);

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
    public function acl($modelClassName, $builder = null, $context = null, $arguments = []) {


        if (!$builder) {
            $builder = new $modelClassName;
            $arguments[1] = $builder;
        }

        $user = $this->resolveUser();

        $arguments = Arr::wrap($arguments);

        // First we will call the "before" callbacks for the Gate. If any of these give
        // back a non-null response, we will immediately return that result in order
        // to let the developers override all checks for some authorization cases.
        $result = $this->callBeforeAclCallbacks(
            $user, $modelClassName, $context, $builder, $arguments
        );

        if (is_null($result)) {
            $result = $this->callAclCallback($user, $modelClassName, $builder, $context, $arguments);
        }

        return $result;

    }


    /**
     * @param Builder $builder
     * @return mixed
     */
    protected function buildBuilderMethod($type,$builder,$modelClassName = null) {

        if (!in_array($type,['all','none'])) {
            throw new InvalidArgumentException('acl method type not allowed: it must be wither "all" or "none"');
        }

        $aclType = Arr::get($this->builderMethods,$type);

        if (!is_null($aclType)) {
            $aclTypeFunc = $aclType;
            return $aclTypeFunc($builder,$modelClassName);
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
    protected function callAclCallback($user, $modelClassName, $builder, $context, array $arguments)
    {
        $callback = $this->resolveAclCallback($user, $modelClassName, $builder, $context, $arguments);

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
    protected function resolveAclCallback($user, $modelClassName, $builder, $context, array $arguments)
    {
        if (!is_null($policy = $this->gate->getPolicyFor($modelClassName)) &&
            $callback = $this->resolvePolicyAclCallback($user, $builder, $context, $arguments, $policy)) {
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
    protected function resolvePolicyAclCallback($user, $builder, $context, array $arguments, $policy)
    {
//        if (! is_callable([$policy, $this->formatContextToAclMethod($context)])) {
//            return false;
//        }

        return function () use ($user, $context, $builder, $arguments, $policy) {

            $result = $this->callPolicyBeforeAcl(
                $policy, $user, $context, $builder, $arguments
            );

            // When we receive a non-null result from this before method, we will return it
            // as the "final" results. This will allow developers to override the checks
            // in this policy to return the result for all rules defined in the class.
            if (! is_null($result)) {
                return $result;
            }

            $aclMethod = $this->formatContextToAclMethod($context);

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
    protected function callPolicyBeforeAcl($policy, $user, $context, $builder, $arguments)
    {
        if (method_exists($policy, 'beforeAcl')) {
            return $policy->beforeAcl($user, $context, $builder, ...$arguments);
        }
    }

    /**
     * Format the policy ability into a method name.
     *
     * @param  string  $ability
     * @return string
     */
    protected function formatContextToAclMethod($context = null)
    {
        return 'acl'. ($context ?: Str::camel($context));
    }


    public function all($builder,$modelClassName = null) {
        return $this->buildBuilderMethod('all', $builder, $modelClassName);
    }

    public function none($builder,$modelClassName = null) {
        return $this->buildBuilderMethod('none', $builder, $modelClassName);
    }

}
