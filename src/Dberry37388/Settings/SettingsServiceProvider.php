<?php namespace Dberry37388\Settings;

use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('dberry37388/settings');

		$this->app['dberry37388.settings']->load();
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['dberry37388.settings'] = $this->app->share(function($app)
		{
			return new \Dberry37388\Settings\Settings($app);
		});

		$this->app['dberry37388.site'] = $this->app->share(function($app)
		{
			return new \Dberry37388\Settings\Site($app);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}