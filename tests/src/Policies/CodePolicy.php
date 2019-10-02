<?php

namespace Gecche\AclTest\Tests\Policies;

use Gecche\AclTest\Tests\Models\Code;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CodePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return mixed
     */
    public function acl(User $user, $builder)
    {


        if ($user->hasRole('Admin')) {
            return $builder->where('id','>',9);
        } else return $builder->where('id',8);

    }
}
