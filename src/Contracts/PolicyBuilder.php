<?php

namespace Gecche\PolicyBuilder\Contracts;

use Gecche\PolicyBuilder\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

interface PolicyBuilder
{

    /**
     * Register a callback to run before all Gate checks.
     *
     * @param  callable  $callback
     * @return $this
     */

    public function beforeAcl(callable $callback);

    public function all($builder,$modelClassName = null);

    public function none($builder,$modelClassName = null);

    /**
     * @param Builder $builder
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     */
    public function acl($modelClassName, $builder = null, $listType = null, $arguments = []);


    /**
     * @param array $aclMethods
     */
    public function setBuilderMethods($builderMethods);

    /**
     * @param \Closure|null $aclNone
     */
    public function setNoneBuilder($noneBuilder);

    /**
     * @param \Closure|null $aclAll
     */
    public function setAllBuilder($allBuilder);


}
