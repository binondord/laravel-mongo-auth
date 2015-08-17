<?php namespace Reshadman\LmAuth;

#use Illuminate\Foundation\Application;
use Laravel\Lumen\Application;
use Illuminate\Support\ServiceProvider;

class LmAuthServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__.'/lmauth.php' => 'lmauth.php',
		]);

		$this->app['auth']->extend('lmauth', function(Application $app){

			$config = $app['config']->get('lmauth');

			return new MongoDbUserProvider($app['lmauth.collection'], $app['hash'], $config);

		});
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		$config = $this->app['config']->get('lmauth');
		error_log(print_r($config));

		if(is_null($config)) return; // In case there is no config

		if($config['use_default_collection_provider']) {

			if(! is_null($closure = $config['default_connection_closure'])){

				$connection = $closure($this->app);

				$this->app->singleton('lmauth_connection', $connection);

			} else {

				$this->app->singleton('lmauth_connection', function(Application $app){

					return new \MongoClient();

				});

			}

			$this->app->singleton('lmauth_connection', function(Application $app) use($config) {

				$mongoClient = $app['lmauth_connection'];

				return (new MongoConnection($mongoClient, $config))
					->getDefaultDatabase()->{$config['auth_collection_name']};

			});

		}
	}
	
	private function config_path($path)
	{
		if(!function_exists('config_path'))
		{
			return app()->make('path.config').($path ? DIRECTORY_SEPARATOR.$path : $path);
		}else{
			return config_path('lmauth.php');
		}
	}

}
