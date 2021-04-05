<?php

namespace PKP\Events;

import('lib.pkp.classes.publication.PKPPublication');

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class TestQueueEvent implements ShouldQueue {
	use Dispatchable, InteractsWithSockets, SerializesModels;

	public \PKPPublication $publication;

	public int $delay = 10;

	public string $connection = 'database';

	public string $queue = 'default';

	public function __construct(\PKPPublication $publication) {
		$this->publication = $publication;
	}

	public function handle(PublicationPublishedEvent $event) {
		$publication = $event->publication;
	}
}

