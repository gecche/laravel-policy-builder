<?php

namespace Gecche\PolicyBuilder\Tests\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Builder;

class CodePolicy
{
    use HandlesAuthorization;


    public function beforeAcl($user, $listType) {

        if ($listType == 'verypublic') {
            return true;
        }



        return null;

    }

    /**
    /*
     * Acl:
     * - all the codes to user 1
     * - all the codes with code starting with "00" to user 2
     * - all the codes with a null description to user 3
     * - only code with id 1 to all other users
     *
     * @param   \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  Builder $builder
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

    /**
    /*
     * Acl admin type:
     * - all the codes to user 1
     * - all the codes with code starting with "00" to user 2
     * - all the codes with a null description to user 3
     * - only code with id 1 to all other users
     *
     * @param   \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  Builder $builder
     * @return mixed
     */
    public function aclAdmin($user, $builder)
    {
        switch ($user->getKey()) {
            case 1:
                return $builder;
            case 2:
            case 3:
                return $builder->where('id','1');
            default:
                return $builder->whereRaw(0);

        }


    }

}
