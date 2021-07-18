<?php

/**
 * @file classes/observers/events/ReviewerReinstated.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerReinstated
 * @ingroup observers_events
 *
 * @brief Event is raised when a reviewer invitation is reinstated
 */

namespace PKP\observers\events;

use Illuminate\Foundation\Events\Dispatchable;
use PKP\submission\PKPSubmission;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\user\User;

class ReviewerReinstated
{
    use Dispatchable;

    /** @var PKPSubmission $submission */
    public $submission;

    /** @var User $reviewer */
    public $reviewer;

    /** @var ReviewAssignment $reviewAssignment */
    public $reviewAssignment;

    public function __construct(PKPSubmission $submission, User $reviewer, ReviewAssignment $reviewAssignment)
    {
        $this->submission = $submission;
        $this->reviewer = $reviewer;
        $this->reviewAssignment = $reviewAssignment;
    }
}
