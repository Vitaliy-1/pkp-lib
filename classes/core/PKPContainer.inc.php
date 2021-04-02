<?php

import('lib.pkp.classes.observers.PKPEventServiceProvider');

use Illuminate\Container\Container;
use PKP\Events\PKPEventServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Facade;

class PKPContainer extends Container {

	/**
	 * @return void
	 * @brief Create own container instance, initialize bindings
	 */
	public function __construct() {
		$this->registerBaseBindings();
		$this->registerCoreContainerAliases();
	}

	/**
	 * @return void
	 * @brief Bind the current container and set it globally
	 * let helpers, facades and services know to which container refer to
	 */
	protected function registerBaseBindings() {

		static::setInstance($this);
		$this->instance('app', $this);
		$this->instance(Container::class, $this);

		Facade::setFacadeApplication($this);
		// Load main settings, this should be done before registering services, e.g., it's used by Database Service
		$this->loadConfiguration();
	}

	/**
	 * @return void
	 * @brief Register used service providers within the container
	 */
	public function registerConfiguredProviders() {
		$eventServiceProvider = new PKPEventServiceProvider($this);
		$databaseServiceProvider = new Illuminate\Database\DatabaseServiceProvider($this);

		$eventServiceProvider->register();
		$databaseServiceProvider->register();

		$databaseServiceProvider->boot();
		$eventServiceProvider->boot();
	}

	/**
	 * @return void
	 * @brief Bind aliases with contracts
	 */
	public function registerCoreContainerAliases() {
		foreach([
			'app'              => [self::class, Illuminate\Contracts\Container\Container::class, Psr\Container\ContainerInterface::class],
	        'config'           => [Illuminate\Config\Repository::class, \Illuminate\Contracts\Config\Repository::class],
	        'db'               => [Illuminate\Database\DatabaseManager::class, Illuminate\Database\ConnectionResolverInterface::class],
	        'db.connection'    => [Illuminate\Database\Connection::class, Illuminate\Database\ConnectionInterface::class],
	        'events'           => [\Illuminate\Events\Dispatcher::class, \Illuminate\Contracts\Events\Dispatcher::class],
	        'queue'            => [Illuminate\Queue\QueueManager::class, Illuminate\Contracts\Queue\Factory::class, Illuminate\Contracts\Queue\Monitor::class],
	        'queue.connection' => [Illuminate\Contracts\Queue\Queue::class],
	        'queue.failer'     => [Illuminate\Queue\Failed\FailedJobProviderInterface::class],
        ] as $key => $aliases) {
			foreach ($aliases as $alias) {
				$this->alias($key, $alias);
			}
		}
	}

	/**
	 * @return void
	 * @brief Bind and load container configurations
	 * usage from Facade, see Illuminate\Support\Facades\Config
	 */
	protected function loadConfiguration() {
		$items = [];

		// Database connection
		$driver = strtolower(Config::getVar('database', 'driver'));
		if (substr($driver, 0, 8) === 'postgres') {
			$driver = 'pgsql';
		} else {
			$driver = 'mysql';
		}

		$items['database']['connections'] = [
			'driver'    => $driver,
			'host'      => Config::getVar('database', 'host'),
			'database'  => Config::getVar('database', 'name'),
			'username'  => Config::getVar('database', 'username'),
			'port'      => Config::getVar('database', 'port'),
			'unix_socket'=> Config::getVar('database', 'unix_socket'),
			'password'  => Config::getVar('database', 'password'),
			'charset'   => Config::getVar('i18n', 'connection_charset', 'utf8'),
			'collation' => Config::getVar('database', 'collation', 'utf8_general_ci'),
		];

		$this->instance('config', $config = new Repository($items)); // create instance and bind to use globally
	}
}
