<?php namespace Dberry37388\Settings\Models;

use LaravelBook\Ardent\Ardent;

class SettingsModel extends Ardent {

	/**
	 * Holds our table name
	 *
	 * @var string
	 */
	protected $table = 'dberry37388_settings';

	/**
	 * Our Validation Rules
	 * 
	 * @var array
	 */
	public static $rules = array(
		'namespace',
		'group'     => 'required',
		'item'      => 'required',
		'format'    => 'required|in:string,array'
	);

	/**
	 * Enable/Disable Auto Hydration
	 *
	 * Allows Ardent to populate our model using the input
	 * fields set in our rules.
	 * 
	 * @var boolean
	 */
	public $autoHydrateEntityFromInput = true;

}