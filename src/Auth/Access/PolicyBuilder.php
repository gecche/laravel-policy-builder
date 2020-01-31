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
     * Get a PolicyBuilder instance for the given user.
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
        $builderMethodsTypes = array_keys($builderMethods);
        foreach ($builderMethodsTypes as $type) {
            if (!in_array($type,['all','none'])) {
                throw new InvalidArgumentException('policy builder method type not allowed: it must be wither "all" or "none"');
            }
        }

        $this->builderMethods = $builderMethods;
    }


    /**
     * Method for setting the function to be called when PolicyBuilder@none method is called
     *
     * @param \Closure|null $noneBuilder
     */
    public function setNoneBuilder($noneBuilder)
    {
        $this->builderMethods['none'] = $noneBuilder;
    }

    /**
     *
     * Method for setting the function to be called when PolicyBuilder@all method is called
     *
     * @param \Closure|null $allBuilder
     */
    public function setAllBuilder($allBuilder)
    {
        $this->builderMethods['all'] = $allBuilder;
    }


    /**
     * Register a callback to run before all Policybuilder checks.
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
     * Call all of the beforeAcl callbacks and return if a result is given.
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
     *
     * Find out the policy associated to the model $modelClassName and then
     * applies its beforeAcl method (if any) to the $builder adn the given $context.
     * If no return is obtained, the policy's acl method is executed and the instantiated
     * Eloquent Builder is returned.
     *
     * @param string $modelClassName
     * @param Builder|null $builder
     * @param string|null $context
     * @param array $arguments
     * @return Builder
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
     *
     * Create the callback to be executed for the PolicyBuilder@all and PolicyBuilder@none methods.
     * If they are not defined, either the $builder is returned as is ("all" $type) or the $builder is returned
     * constrained with a filter for getting an empty query result ("none" $type)
     *
     * @param string $type ('all', 'none')
     * @param Builder $builder
     * @param string $modelClassName
     * @return mixed
     */
    protected function buildBuilderMethod($type,$builder,$modelClassName = null) {

        $policyBuilderDefaultType = Arr::get($this->builderMethods,$type);


        if (!is_null($policyBuilderDefaultType)) {
            $aclTypeFunc = $policyBuilderDefaultType;
            return $aclTypeFunc($builder,$modelClassName);
        }

        switch ($type) {
            case 'all':
                //$builder is returned with no constraints
                return $builder;
            case 'none':
                //$builder is returned with a "false" constraint which should return an empty result for a query
                return $builder->whereRaw(0);
        }
    }


    /**
     * Resolve and call the appropriate policy's acl callback.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $modelClassName
     * @param  Builder $builder
     * @param  string $context
     * @param  array  $arguments
     * @return Builder
     */
    protected function callAclCallback($user, $modelClassName, $builder, $context, array $arguments)
    {
        $callback = $this->resolveAclCallback($user, $modelClassName, $builder, $context, $arguments);

        return $callback($user, $builder, ...$arguments);
    }


    /**
     * Resolve the appropriate policy's acl callback if any.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $modelClassName
     * @param  Builder $builder
     * @param  string $context
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
     * Resolve the appropriate policy's acl callback if any.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  Builder $builder
     * @param  string $context
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
     * Call the "beforeAcl" method on the given policy, if applicable.
     *
     * @param  mixed  $policy
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string $context
     * @param  Builder $builder
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
     * Format the requested list "context" into a method name.
     *
     * @param  string  $context
     * @return string
     */
    protected function formatContextToAclMethod($context = null)
    {
        return 'acl'. ($context ?: Str::camel($context));
    }


    /**
     * Apply to $builder the "all" filters for getting a list of all models
     *
     * @param Builder $builder
     * @param string|null $modelClassName
     * @return mixed
     */
    public function all($builder, $modelClassName = null) {
        return $this->buildBuilderMethod('all', $builder, $modelClassName);
    }

    /**
     * Apply to $builder the "none" filters for getting an empty list of models
     *
     * @param Builder $builder
     * @param string|null $modelClassName
     * @return mixed
     */
    public function none($builder, $modelClassName = null) {
        return $this->buildBuilderMethod('none', $builder, $modelClassName);
    }

}
