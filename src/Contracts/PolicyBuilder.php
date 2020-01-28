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

    public function aclAll($builder);

    public function aclNone($builder);

    /**
     * @param Builder $builder
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     */
    public function acl($modelClassName, $builder = null, $listType = null, $arguments = []);

    public function aclGuest($builder);

    /**
     * @param \Closure|null $aclAll
     */
    public function setAclGuest($aclGuest);

    /**
     * @param array $aclMethods
     */
    public function setAclMethods($aclMethods);

    /**
     * @param \Closure|null $aclNone
     */
    public function setAclNone($aclNone);

    /**
     * @param \Closure|null $aclAll
     */
    public function setAclAll($aclAll);


}
