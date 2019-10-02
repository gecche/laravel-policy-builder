<?php namespace Gecche\AclTest\Tests\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Code
 *
 * @package Gecche\AclTest\Tests\Models
 *
 */
class Code extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'codes';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public $fillable = ['code','description'];

}
