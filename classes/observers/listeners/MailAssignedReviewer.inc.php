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
 * @brief Send email to a reviewer about being assigned to a submission
 */

namespace PKP\observers\listeners;

use PKP\observers\events\ReviewerAssigned;

class MailAssignedReviewer
{

    /**
     * @param \PKP\observers\events\ReviewerAssigned
     * @return void
     */
    public function handle(ReviewerAssigned $event)
    {
        $request = $event->request;
        if ($request->getUserVar('skipEmail')) return;

        $templateKey = $request->getUserVar('template');
    }
}
