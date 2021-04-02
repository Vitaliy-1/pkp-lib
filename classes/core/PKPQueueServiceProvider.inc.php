<?php

use Illuminate\Queue\QueueServiceProvider;
use Illuminate\Queue\Connectors\DatabaseConnector;

class PKPQueueServiceProvider extends QueueServiceProvider {

	public function register() {
		$this->registerManager();
		$this->registerConnection();
		$this->registerWorker();
		$this->registerListener();
	}

	/**
	 * @param \Illuminate\Queue\QueueManager $manager
	 * return void
	 * @brief Register connectors in the Container, see Illuminate\Queue\QueueServiceProvider::registerConnectors
	 */
	public function registerConnectors($manager) {
		foreach (['Null', 'Sync', 'Database'] as $connector) {
			$this->{"register{$connector}Connector"}($manager);
		}
	}

	/**
	 * Register the database queue connector.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $manager
	 * @return void
	 */
	protected function registerDatabaseConnector($manager)
	{
		$manager->addConnector('database', function () {
			return new DatabaseConnector($this->app['db']);
		});
	}

}
