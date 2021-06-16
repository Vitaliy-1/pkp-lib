<?php

/**
 * @file classes/observers/events/UserRegisteredSite.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserRegisteredSite
 * @ingroup observers_events
 *
 * @brief Event is raised when user is successfully registered from the site
 */

namespace PKP\observers\events;

use Illuminate\Foundation\Events\Dispatchable;
use PKP\context\Context;
use PKP\core\NotificationEvent;
use PKP\site\Site;
use PKP\user\User;

class UserRegisteredSite extends NotificationEvent
{
    use Dispatchable;

    /**
     * @var User $recipient
     */
    public $recipient;

    /**
     * @var Site $site
     */
    public $site;

    public function __construct(User $recipient, Site $site)
    {
        $this->recipient = $recipient;
        $this->site = $site;
    }
}
