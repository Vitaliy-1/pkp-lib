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

use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use PKP\user\UserModel;

class UserGroupModel extends Model
{
    use HasCamelCasing;

    protected $table = 'user_groups';
    protected $primaryKey = 'user_group_id';

    /**
     * Cast attributes to their native type
     *
     * @var <string, string>
     */
    protected $casts = [
        'userGroupId' => 'int',
        'contextId' => 'int',
        'roleId' => 'int',
        'isDefault' => 'boolean',
        'showTitle' => 'boolean',
        'permitSelfRegistration' => 'boolean',
        'permitMetadataEdit' => 'boolean',
        'masthead' => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(UserModel::class, 'user_user_groups', 'user_group_id', 'user_id');
    }
}
