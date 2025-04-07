<?php

/**
 * @file classes/security/authorization/TaskAccessPolicy.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class TaskAccessPolicy
 *
 * @ingroup security_authorization
 *
 * @brief Class to permit access to the tasks and discussions
 */

namespace PKP\security\authorization;

class TaskAccessPolicy extends QueryAccessPolicy
{
    public function __construct($request, $args, $roleAssignments, $stageId)
    {
        parent::__construct($request, $args, $roleAssignments, $stageId);
    }
}
