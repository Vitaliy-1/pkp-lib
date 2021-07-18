<?php

/**
 * @file classes/observers/listeners/MailAssignedReviewer.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MailAssignedReviewer
 * @ingroup core
 *
 * @brief Send validation email for the registered user if required by config
 */

namespace PKP\mail\mailables;

use PKP\context\Context;
use PKP\mail\Mailable;
use PKP\submission\PKPSubmission;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\user\User;

class MailAssignedReviewer extends Mailable
{
    public function __construct(Context $context, PKPSubmission $submission, ReviewAssignment $reviewAssignment, User $sender, array $recipients)
    {
        parent::__construct(func_get_args());
    }
}
