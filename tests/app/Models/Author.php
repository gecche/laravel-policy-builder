<?php namespace Gecche\PolicyBuilder\Tests\App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Code
 *
 * @package Gecche\AclTest\Tests\Models
 *
 */
class Author extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'authors';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public $guarded = ['id'];

}
