<?php

namespace PKP\Events;

import('lib.pkp.classes.publication.PKPPublication');

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class PublicationPublishedEvent {
	use Dispatchable, InteractsWithSockets, SerializesModels;

	public \PKPPublication $publication;

	public function __construct(\PKPPublication $publication) {
		$this->publication = $publication;
	}
}

