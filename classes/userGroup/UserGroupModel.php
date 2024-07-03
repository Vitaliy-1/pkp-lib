<?php

/**
 * @file classes/userGroup/UserGroupModel.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class \PKP\userGroup\UserGroupModel
 *
 * @brief UserGroup Model class.
 */

namespace PKP\userGroup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use PKP\user\UserModel;

class UserGroupModel extends Model
{
    protected $table = 'user_groups';
    protected $primaryKey = 'user_group_id';

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(UserModel::class);
    }
}
