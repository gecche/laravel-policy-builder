<?php

namespace Gecche\PolicyBuilder\Contracts;

use Gecche\PolicyBuilder\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

interface PolicyBuilder
{

    /**
     * Apply to $builder the "none" filters for getting an empty list of models
     *
     * @param Builder $builder
     * @param string|null $modelClassName
     * @return mixed
     */
    public function none($builder, $modelClassName = null);

    /**
     * Apply to $builder the "all" filters for getting a list of all models
     *
     * @param Builder $builder
     * @param string|null $modelClassName
     * @return mixed
     */
    public function all($builder, $modelClassName = null);

    /**
     *
     * Method for setting the function to be called when PolicyBuilder@all method is called
     *
     * @param \Closure|null $allBuilder
     */
    public function setAllBuilder($allBuilder);

    /**
     * Method for setting the function to be called when PolicyBuilder@none method is called
     *
     * @param \Closure|null $noneBuilder
     */
    public function setNoneBuilder($noneBuilder);

    /**
     * Get a PolicyBuilder instance for the given user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|mixed  $user
     * @return static
     */
    public function forUser($user);

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
    public function acl($modelClassName, $builder = null, $context = null, $arguments = []);

    /**
     * Register a callback to run before all Policybuilder checks.
     *
     * @param  callable  $callback
     * @return \Gecche\PolicyBuilder\Auth\Access\PolicyBuilder
     */
    public function beforeAcl(callable $callback);

    /**
     * @param array $builderMethods
     */
    public function setBuilderMethods($builderMethods);


}
