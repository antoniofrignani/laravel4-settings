<?php namespace Dberry37388\Settings;

use Config;

class Site {

	/**
	 * Holds the namespace for our key
	 *
	 * @var string
	 */
	protected $namespace = 'site';

	/**
	 * Holds our registered custom macros
	 *
	 * @var array
	 */
	public $macros = array();


	public function __construct($app)
	{
		// if we have a custom configured namespace, then use that. If not default to our
		// hard-coded namespace of site
		$this->namespace = Config::get('settings::site.namespace', $this->namespace);

		$this->settings = $app['dberry37388.settings'];
	}

	/**
	 * Get a value from our site config
	 *
	 * @param  string $key
	 * @param  string $default
	 * @return string
	 */
	public function get($key = '', $default = '')
	{
		if (empty($key))
		{
			return;
		}

		return $this->settings->get("{$this->namespace}.{$key}", $default);
	}

	/**
	 * Sets a value in our site config
	 *
	 * @param string $key
	 * @param string
	 *
	 * @return  void
	 */
	public function set($key = '', $value = '')
	{
		if (empty($key))
		{
			return;
		}

		$this->settings->setTemp("{$this->namespace}.{$key}", $value);
	}

	/**
	 * Set multiple values at one time
	 *
	 * Example:
	 * $attributes = array(
	 * 	'page_tile' => 'This is My Page Title',
	 * 	'section'   => 'Users'
	 * );
	 *
	 * {$this->namespace}.setMultiple($attributes);
	 *
	 * @param array $keys
	 */
	public function setMultiple($keys = array())
	{
		if (is_array($keys) and ! empty($keys))
		{
			foreach ($keys as $key=>$value)
			{
				$this->set($key, $value);
			}
		}
	}

	/**
	 * Registers a custom macro.
	 *
	 * @param  string   $name
	 * @param  Closure  $macro
	 * @return void
	 */
	public function macro($name, $macro)
	{
		$this->macros[$name] = $macro;

		// dd($this->macros, true);
	}

	/**
	 * Checks to see if a uri matches our pattern
	 *
	 * You can pass an optional class for using with css. This can actually be anything
	 * that you may want to return.
	 *
	 * If the second parameter is empty, this will just return true.
	 *
	 * @param  string  $pattern
	 * @param  string  $class
	 *
	 * @return mixed
	 */
	public function uriIs($pattern, $class)
	{
		// use Laravel's built-in method to check for matching uri
		if (Request::is($pattern))
		{
			// check to see if a class has been set
			if ( ! empty($class))
			{
				// return our css class
				return $class;
			}

			// no class is set, we are just checking
			return true;
		}

		// does not match
		return false;
	}

	/**
	 * Dynamically handle calls to custom macros.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
	    if (isset($this->macros[$method]))
	    {
	        return call_user_func_array($this->macros[$method], $parameters);
	    }

	    throw new \Exception("FUCK YOU!!! Method [$method] does not exist.");
	}
}