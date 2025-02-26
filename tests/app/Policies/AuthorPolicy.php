<?php

namespace Gecche\PolicyBuilder\Tests\App\Policies;

use Gecche\PolicyBuilder\Facades\PolicyBuilder;
use Gecche\PolicyBuilder\Tests\App\Models\Author;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Builder;

class AuthorPolicy
{
    use HandlesAuthorization;


    public function beforeAcl($user, $context, $builder) {

        if (is_null($user)) {
            return PolicyBuilder::none($builder,Author::class);
        }

        return null;

    }

    /**
    /*
     * - All authors are allowed to user 2
     * - Only italian authors are allowed to users 3 and 4
     * - Only non-italian authors are allowed to other users
     *
     * @param   \Illuminate\Contracts\Auth\Authenticatable|null $user
     * @param  Builder $builder
     * @return mixed
     */
    public function acl($user, $builder)
    {
        switch ($user->getKey()) {
            case 2:
                return $builder;
            case 3:
            case 4:
                return $builder->where('nation','IT');
            default:
                return $builder->where('nation','<>','IT');

        }


    }

    /**
    /*
     * In "editing" context
     * - only user 2 can access to users, but only those born before 1900
     *
     * @param   \Illuminate\Contracts\Auth\Authenticatable|null $user
     * @param  Builder $builder
     * @return mixed
     */
    public function aclEditing($user, $builder)
    {

        switch ($user->getKey()) {
            case 2:
                return $builder->where('birthdate','<','1900-01-01');
            default:
                return PolicyBuilder::none($builder,Author::class);

        }


    }

}
