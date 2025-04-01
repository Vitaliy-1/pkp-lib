<?php

/**
 * @file classes/security/authorization/internal/TaskRequiredPolicy.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class QueryRequiredPolicy
 *
 * @ingroup security_authorization_internal
 *
 * @brief Policy that ensures that the request contains a valid query.
 */

namespace PKP\security\authorization\internal;

use APP\core\Application;
use APP\submission\Submission;
use PKP\core\PKPRequest;
use PKP\editorialTask\Task;
use PKP\security\authorization\AuthorizationPolicy;
use PKP\security\authorization\DataObjectRequiredPolicy;

class TaskRequiredPolicy extends DataObjectRequiredPolicy
{
    public function __construct(PKPRequest $request, array &$args, string $parameterName = 'taskId', array $operations = null)
    {
        parent::__construct($request, $args, $parameterName, 'user.authorization.invalidTask', $operations);
    }

    public function dataObjectEffect()
    {
        $taskId = (int) $this->getDataObjectId();
        if (!$taskId) {
            return AuthorizationPolicy::AUTHORIZATION_DENY;
        }

        // Make sure the task belongs to the submission.
        $task = Task::find($taskId);
        if (!$task instanceof Task) {
            return AuthorizationPolicy::AUTHORIZATION_DENY;
        }

        $submission = $this->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);
        if (!$submission instanceof Submission) {
            return AuthorizationPolicy::AUTHORIZATION_DENY;
        }

        return AuthorizationPolicy::AUTHORIZATION_PERMIT;
    }
}
