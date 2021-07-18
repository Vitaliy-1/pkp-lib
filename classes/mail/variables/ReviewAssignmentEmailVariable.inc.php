<?php

/**
 * @file classes/mail/variables/SubscriptionEmailVariable.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionEmailVariable
 * @ingroup mail_variables
 *
 * @brief Represents email template variables that are associated with a subscription
 */

namespace PKP\mail\variables;

use PKP\core\PKPApplication;
use PKP\submission\reviewAssignment\ReviewAssignment;

class ReviewAssignmentEmailVariable extends Variable
{
    const REVIEW_DUE_DATE = 'reviewDueDate';
    const RESPONSE_DUE_DATE = 'responseDueDate';
    const SUBMISSION_REVIEW_URL = 'submissionReviewUrl';

    /** @var ReviewAssignment $reviewAssignment */
    protected $reviewAssignment;

    public function __construct(ReviewAssignment $reviewAssignment)
    {
        $this->reviewAssignment = $reviewAssignment;
    }

    /**
     * @copydoc Variable::description()
     */
    protected static function description(): array
    {
        return
        [
            self::REVIEW_DUE_DATE => 'Date by which the review is planned to be finished',
            self::RESPONSE_DUE_DATE => 'The final date response is expected to be received',
            self::SUBMISSION_REVIEW_URL => 'URL to the submission review assignment',
        ];
    }

    /**
     * @copydoc Variable::values()
     */
    protected function values(): array
    {
        return
        [
            self::REVIEW_DUE_DATE => $this->getReviewDueDate(),
            self::RESPONSE_DUE_DATE => $this->getResponseDueDate(),
            self::SUBMISSION_REVIEW_URL => $this->getSubmissionUrl(),
        ];
    }

    /**
     * @return string
     */
    protected function getReviewDueDate() : string
    {
        return $this->reviewAssignment->getDateDue();
    }

    /**
     * @return string
     */
    protected function getResponseDueDate() : string
    {
        return $this->reviewAssignment->getDateResponseDue();
    }

    /**
     * URL of the submission for the assigned reviewer
     * @return string URL
     */
    protected function getSubmissionUrl() : string
    {
        $request = PKPApplication::get()->getRequest();
        $dispatcher = $request->getDispatcher();
        return $dispatcher->url(
            $request,
            PKPApplication::ROUTE_PAGE,
            null,
            'reviewer',
            'submission',
            null,
            ['submissionId' => $this->reviewAssignment->getSubmissionId()]
        );
    }
}
