<?php

/**
 * @file classes/observers/events/ReviewerAssigned.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerAssigned
 * @ingroup observers_events
 *
 * @brief Event is raised when editor's decision of adding a reviewer is made
 */

namespace PKP\observers\events;

use PKP\core\NotificationEvent;
use Illuminate\Foundation\Events\Dispatchable;
use PKP\core\PKPRequest;
use PKP\submission\PKPSubmission;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\user\User;

class ReviewerAssigned extends NotificationEvent
{
    use Dispatchable;

    /** @var PKPRequest $request */
    public $request;

    /** @var PKPSubmission $submission */
    public $submission;

    /** @var User $reviewer */
    public $reviewer;

    /* @var ReviewAssignment $reviewAssignment */
    public $reviewAssignment;

    public function __construct(PKPRequest $request, PKPSubmission $submission, User $reviewer, ReviewAssignment $reviewAssignment)
    {
        $this->request = $request;
        $this->submission = $submission;
        $this->reviewer = $reviewer;
        $this->reviewAssignment = $reviewAssignment;
    }
}
