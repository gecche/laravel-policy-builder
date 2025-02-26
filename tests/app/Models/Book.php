<?php namespace Gecche\PolicyBuilder\Tests\App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Code
 *
 * @package Gecche\AclTest\Tests\Models
 *
 */
class Book extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'books';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public $guarded = ['id'];

    public function author() {

        return $this->belongsTo(Author::class, null, null, null);

    }

}
