<?php

/**
 * @file classes/invitation/invitations/userRoleAssignment/rules/NoUserGroupChangesRule.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NoUserGroupChangesRule
 *
 * @brief Payload for the assign Roles to User invitation
 */

namespace PKP\invitation\invitations\userRoleAssignment\rules;

use Illuminate\Contracts\Validation\Rule;

class NoUserGroupChangesRule implements Rule
{
    protected ?array $userGroupsToAdd;
    protected string $validationContext;

    public function __construct(?array $userGroupsToAdd)
    {
        $this->userGroupsToAdd = $userGroupsToAdd;
    }

    public function passes($attribute, $value)
    {
        return !empty($this->userGroupsToAdd);
    }

    public function message()
    {
        return __('invitation.userRoleAssignment.validation.error.noUserGroupChanges');
    }
}