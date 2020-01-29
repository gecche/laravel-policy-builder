<?php namespace Gecche\PolicyBuilder\Facades;

use Illuminate\Support\Facades\Facade;
/**
 * @see \Illuminate\Database\Schema\Builder
 */
class PolicyBuilder extends Facade {


	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{

        return \Gecche\PolicyBuilder\Contracts\PolicyBuilder::class;
    }

}
