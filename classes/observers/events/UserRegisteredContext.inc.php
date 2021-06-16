<?php

/**
 * @file classes/observers/events/UserRegisteredContext.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserRegisteredContext
 * @ingroup observers_events
 *
 * @brief Event is raised when user is successfully registered from the context (journal or press)
 */

namespace PKP\observers\events;

use Illuminate\Foundation\Events\Dispatchable;
use PKP\context\Context;
use PKP\core\NotificationEvent;
use PKP\site\Site;
use PKP\user\User;

class UserRegisteredContext extends NotificationEvent
{
    use Dispatchable;

    /**
     * @var User $registeredUser
     */
    public $registeredUser;

    /**
     * @var Context $context
     */
    public $context;

    public function __construct(User $registeredUser, Context $context)
    {
        $this->registeredUser = $registeredUser;
        $this->context = $context;
    }
}
