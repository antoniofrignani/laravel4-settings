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
		// we do not want to run this in the cli
		if ($app->runningInConsole() or $this->isLoaded === true)
		{
			return;
		}
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
		// parse our key, using the Illuminate NamespaceResolver
		list($namespace, $group, $item) = $this->parseKey($key);

		// namespaces and groups our key.
		$collection = $this->getCollection($group, $namespace);

		// we will return it. If not, then we'll get the default that we set.
		return Config::get("{$collection}.{$item}", $default);
	}

	/**
	 * Sets a Value with option for temporary value.
	 *
	 * By default, this will save the setting to the database so that it will
	 * persist.  If temporary is set to false, then it will only store it in
	 * memory.
	 * 
	 * @param  string  $key
	 * @param  string  $value
	 * @param  boolean $temporary
	 * @return void
	 */
	public function set($key, $value = '', $temporary = false)
	{
		// let's parse our key
		list($namespace, $group, $item) = $this->parseKey($key);

		// set our collection
		$collection = $this->getCollection($group, $namespace);

		// We'll need to go ahead and lazy load each configuration groups even when
		// we're just setting a configuration item so that the set item does not
		// get overwritten if a different item in the group is requested later.
		$this->load($group, $namespace, $collection);

		if ($temporary === false)
		{
			// query our for the setting. If the setting already exists,
			// then we will return it's object. If it does not exist,
			// then we will get a new SettingsModel Object
			$this->saveModelObject($value, $group, $item, $namespace);
		}

		// set the item in our config
		Config::set("{$collection}.{$item}", $value);
	}

	/**
	 * Temporarily stores a value in memory. This will not save it to the
	 * database, so you will lose it's value on the next request.
	 * 
	 * @param  string $key
	 * @param  string $value
	 * @return void
	 */
	public function setTemp($key, $value = '')
	{
		$this->set($key, $value, true);
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
		$collection = $this->getCollection($group, $namespace);

		// if it is stored in the DB, let's delete it.
		$setting = $this->getModelObject($group, $item, $namespace);

		if ( ! empty($setting))
		{
			$setting->delete();
		}

		// remove it from our config array
		Config::set("{$collection}.{$item}", null);
	}

	/**
	 * Load the configuration group for the key.
	 *
	 * @param  string  $key
	 * @param  string  $namespace
	 * @param  string  $collection
	 * @return void
	 */
	protected function load($group, $namespace, $collection)
	{
		// get our collection from the Config
		$items = Config::get($collection);
	}

	public function loader()
	{
		// retrieve all of our settings from the database
		$settings = SettingsModel::all();

		// go through each setting and store it in the config.
		foreach ($settings as $setting)
		{
			$namespace = $setting->namespace;
			$group     = $setting->group;
			$item      = $setting->item;
			$value     = $setting->value;

			$collection = $this->getCollection($group, $namespace);

			if (empty($collection))
			{
				$this->items[$collection] = $value;
			}
			else
			{
				array_set($this->items[$collection], $item, $value);
			}

			// $config = app()['config']->get($collection);

			// var_dump($config); exit;
			
			// set the item in our config
			Config::set("{$collection}.{$item}", $value);

			unset($namespace);
			unset($group);
			unset($item);
			unset($value);
		}

		$this->isLoaded = true;
	}

	/**
	 * Returns an instance of the SettingsModel
	 *
	 * If we already have the setting stored, it will return the instance of
	 * that setting. If not, then we will get back a new clean object.
	 * 
	 * @param  string $group
	 * @param  string $item
	 * @param  string $namespace
	 * @return SettingsModel
	 */
	protected function getModelObject($group, $item, $namespace = '')
	{
		$setting = SettingsModel::where('namespace', '=', $namespace)
							->where('group',   '=', $group)
							->where('item',    '=', $item)
							->first();

		if (empty($setting))
		{
			$setting = new SettingsModel;
		}

		return $setting;
	}

	protected function saveModelObject($value, $group, $item, $namespace = '')
	{
		// query our for the setting. If the setting already exists,
		// then we will return it's object. If it does not exist,
		// then we will get a new SettingsModel Object
		$setting = $this->getModelObject($group, $item, $namespace);

		// format our value
		list($value, $format) = $this->detectSettingFormat($value);

		// save our object data
		$setting->namespace = $namespace;
		$setting->group     = $group;
		$setting->item      = $item;
		$setting->format    = $format;
		$setting->value     = $value;

		if ($setting->save())
		{
			return $setting;
		}

		return false;
	}

	/**
	 * Get the collection identifier.
	 *
	 * @param  string  $group
	 * @param  string  $namespace
	 * @return string
	 */
	protected function getCollection($group, $namespace = null)
	{
		$namespace = $namespace ? "{$namespace}::" : '';

		return $namespace.$group;
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