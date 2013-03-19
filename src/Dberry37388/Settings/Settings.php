<?php namespace Dberry37388\Settings;

use Illuminate\Support\NamespacedItemResolver;
use Dberry37388\Settings\Models\SettingsModel;
use Config;
use App;

class Settings extends NamespacedItemResolver {

	/**
	 * Holds all of our configuration (settings) items
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * Whether or not our config has already been loaded from disk
	 *
	 * @var boolean
	 */
	protected $isLoaded = false;


	public function __construct($app)
	{
		// Load Our Settings
		// This will only happen one time. Once the items are loaded they will
		// stay in memory so we don't keep reloading them.
		$this->load();
	}

	/**
	 * Checks to see if a Setting Exists
	 *
	 * @param  string  $key  key we are checking
	 *
	 * @return boolean
	 */
	public function has($key)
	{
		$default = microtime(true);
		return $this->get($key, $default) != $default;
	}

	/**
	 * Retrieves the specified setting.
	 *
	 * @param  string $key     key we are retrieving
	 * @param  mixed $default a default value if the key does not exist.
	 *
	 * @return mixed
	 */
	public function get($key = '', $default = '')
	{

		// i'm leaving this commented code in here for right now, trying to decide if I actually
		// need it or not. Since when we set a value, we are also adding it to the Laravel default
		// Config, we should just be able to call Config::get() to get all of our values.

		// // parse our key, using the Illuminate NamespaceResolver
		// list($namespace, $group, $item) = $this->parseKey($key);

		// // namespaces and groups our key.
		// $collection = $this->getCollection($namespace, $group);

		// // check to see if we are workign with a collection (namespace::group)
		// if (isset($this->items[$collection]))
		// {
		// 	// check to see if we are looking for a specific item.
		// 	if (isset($this->items[$collection][$item]))
		// 	{
		// 		// we found the item, let's return it's value.
		// 		$value = array_get($this->items[$collection], $item, $default);
		// 	}
		// 	elseif(empty($item))
		// 	{
		// 		// we are not looking for a specific item, so let's return the whole collection.
		// 		$value = array_get($this->items[$collection], $item, $default);
		// 	}
		// }

		// okay, so we didn't find it in our settings already. So now we will
		// check to see if it exists in the native Config settings. If it is there,
		// we will return it. If not, then we'll get the default that we set.
		return Config::get($key, $default);
	}

	/**
	 * Returns all of our settings
	 *
	 * @return array
	 */
	public function getAll()
	{
		return $this->items;
	}

	/**
	 * Grabs all of the config items stored in our settings table and loads them to memory.
	 *
	 * @return void
	 */
	public function load()
	{
		// if we have already loaded our settings, let's not do it again.
		// after the first load, settings are stored in memory.
		if ($this->isLoaded === true || App::runningInConsole())
		{
			return;
		}

		// load all of the settings that we have stored in the database
		// using our SettingsModel
		$allSettings = SettingsModel::all();

		if ( ! empty($allSettings))
		{
			foreach ($allSettings as $setting)
			{
				$this->loadSetting($setting);
			}

			// make sure we set our isLoaded to true, that way we are not
			// continuously loading our settings over and over.
			$this->isLoaded = true;
		}

	}

	/**
	 * Sets a value
	 *
	 * @param string $key   key for our setting
	 * @param mixed $value value to set
	 *
	 * @return void
	 */
	public function set($key, $value = '')
	{
		// try to look up our setting to see if it already exists
		$setting = SettingsModel::where('name', '=', $key)->first();

		// detect the format of our value and return the proper insert information
		list($value, $format) = $this->detectSettingFormat($value);


		if ($setting)
		{
			$setting->value  = $value;
			$setting->format = $format;
			$setting->save();
		}
		else
		{
			$setting = SettingsModel::create(array(
				'name'   => $key,
				'value'  => $value,
				'format' => $format
			));
		}

		// load our saved setting info to memory
		$this->loadSetting($setting);
	}

	/**
	 * Sets multiple values at one time
	 *
	 * Example:
	 * $attributes = array(
	 * 	'page_tile' => 'This is My Page Title',
	 * 	'section'   => 'Users'
	 * );
	 *
	 * Settings::setMultiple($attributes);
	 *
	 * @param array $keys
	 */
	protected function setMultiple($keys = array())
	{
		if (is_array($keys) and ! empty($keys))
		{
			foreach($keys as $key=>$value)
			{
				$this->set($key, $value);
			}
		}
	}

	/**
	 * Sets a temporary value in memory
	 *
	 * Anything set here is not persistent and is only for the current request.
	 * This could be useful for things like setting a page title, section name, whatever.
	 *
	 * @param string $key    key name
	 * @param string $value  value to save
	 *
	 * @return void
	 */
	public function setTemp($key, $value = '')
	{
		// parse our key, using the Illuminate NamespaceResolver
		list($namespace, $group, $item) = $this->parseKey($key);

		// namespaces and groups our key.
		$collection = $this->getCollection($namespace, $group);

		// format our value
		list($value, $format) = $this->detectSettingFormat($value);

		// add our settings to our items array
		$this->items[$collection][$item] = $this->formatSetting($value, $format);

		// now let's set and overwrite the base laravel config if we need to
		Config::set($key, $this->items[$collection][$item]);
	}

	/**
	 * Deletes a setting from memory and the database
	 *
	 * @param  string $key
	 * @return void
	 */
	public function forget($key)
	{
		// parse our key, using the Illuminate NamespaceResolver
		list($namespace, $group, $item) = $this->parseKey($key);

		// namespaces and groups our key.
		$collection = $this->getCollection($namespace, $group);

		if (isset($this->items[$collection]))
		{
			$setting = array_forget($this->items[$collection], $item);

			SettingsModel::where('name', '=', $key)->delete();
		}
	}

	/**
	 * Loads a setting to memory
	 *
	 * Sets up our items array and handles setting it in the native Laravel Config
	 *
	 * @param  string $setting our setting object
	 *
	 * @return void
	 */
	protected function loadSetting($setting)
	{
		// parse our key, using the Illuminate NamespaceResolver
		list($namespace, $group, $item) = $this->parseKey($setting->name);

		// namespaces and groups our key.
		$collection = $this->getCollection($namespace, $group);

		// add our settings to our items array
		$this->items[$collection][$item] = $this->formatSetting($setting->value, $setting->format);

		// now let's set and overwrite the base laravel config if we need to
		Config::set($setting->name, $this->items[$collection][$item]);
	}

	/**
	 * Sets the namespace and group collection for our key
	 *
	 * Makes use of Laravel's NamespaceResolver
	 *
	 * @param  string  $key  the key we are working with
	 *
	 * @return string
	 */
	protected function getCollection($namespace, $group)
	{
		// return our collection.
		return empty($namespace) ? $group : "{$namespace}::{$group}"; exit;
	}

	/**
	 * Formats our DB stored value
	 *
	 * When saving a setting to the database, you can either store a string value or an array
	 * of values stored as a json_string.  This is a simple method to make sure we are returning
	 * a properly formatted value.
	 *
	 * @param  string  $value   the value we want to formatt
	 * @param  string  $format  format our value was stored in
	 *
	 * @return string
	 */
	protected function formatSetting($value, $format)
	{
		if ($format === 'json')
		{
			return json_decode($value);
		}

		return $value;
	}

	/**
	 * Detects our format and returns a properly formatted setting
	 *
	 * Settings can either be strings or arrays. If we have an array, we need to json_encode it
	 * so that it will go into the database properly.
	 *
	 * @param  mixed  $value  value to format
	 *
	 * @return mixed
	 */
	protected static function detectSettingFormat($value)
	{
		if (is_array($value))
		{
			$setting = array(
				json_encode($value),
				'json'
			);
		}
		else
		{
			$setting = array(
				$value,
				'string'
			);
		}

		return $setting;
	}
}