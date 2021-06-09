<?php

/**
 * @file classes/observers/events/DiscussionMessageSent.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DiscussionMessageSent
 * @ingroup observers_events
 *
 * @brief Event is raised when discussion message is sent regardless of the workflow stage
 */

namespace PKP\observers\events;

use PKP\core\NotificationEvent;
use Illuminate\Foundation\Events\Dispatchable;
use PKP\core\PKPRequest;
use PKP\query\Query;

class DiscussionMessageSent extends NotificationEvent
{
    use Dispatchable;

    /** @var Query $query */
    public $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }
}
