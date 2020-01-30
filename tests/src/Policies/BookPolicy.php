<?php

namespace Gecche\PolicyBuilder\Tests\Policies;

use Gecche\PolicyBuilder\Facades\PolicyBuilder;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Builder;

class BookPolicy
{
    use HandlesAuthorization;


    /**
    /*
     * For books, registered users have access to all the models,
     * so we implement only the logic for guest user which has
     * access to books whose author's name is 'Ken' or written in italian
     *
     */
    public function acl($user, $builder)
    {
        return $builder->where(function ($q) {
            return $q->where('language','IT')
                ->orWhereHas('author',function($q) {
                    return $q->where('name','Ken');
                });
        });
    }


}
