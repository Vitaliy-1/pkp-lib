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

use PKP\submission\reviewAssignment\ReviewAssignment;

class ReviewAssignmentEmailVariable extends Variable
{
    const REVIEW_DUE_DATE = 'reviewDueDate';
    const RESPONSE_DUE_DATE = 'responseDueDate';

    /** @var ReviewAssignment $reviewAssignment */
    protected $reviewAssignment;

    public function __construct(ReviewAssignment $reviewAssignment)
    {
        $this->reviewAssignment = $reviewAssignment;
    }

    /**
     * @return string[]
     * @brief see Variable::description()
     */
    protected static function description(): array
    {
        return
        [
            self::REVIEW_DUE_DATE => 'Date by which the review is planned to be finished',
            self::RESPONSE_DUE_DATE => 'The final date response is expected to be received',
        ];
    }

    /**
     * @return array
     * @brief see Variable::values()
     */
    protected function values(): array
    {
        return
        [
            self::REVIEW_DUE_DATE => $this->getReviewDueDate(),
            self::RESPONSE_DUE_DATE => $this->getResponseDueDate(),
        ];
    }

    protected function getReviewDueDate() : int
    {
        return strtotime($this->reviewAssignment->getDateDue());
    }

    protected function getResponseDueDate() : int
    {
        return strtotime($this->reviewAssignment->getDateResponseDue());
    }
}
