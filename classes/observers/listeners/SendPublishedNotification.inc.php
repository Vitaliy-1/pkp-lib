<?php

namespace PKP\Listeners;

use PKP\Events\PublicationPublishedEvent;

class SendPublishedNotification {
	public function __construct() {

	}

	public function handle(PublicationPublishedEvent $event) {
		$publication = $event->publication;
	}
}

