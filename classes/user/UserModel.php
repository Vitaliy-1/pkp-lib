<?php
/**
 * @file classes/user/UserModel.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserModel
 *
 * @ingroup UserModel
 *
 * @brief Basic class describing users existing in the system.
 */

namespace PKP\user;

use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder;
use PKP\userGroup\UserGroupModel;

class UserModel extends Model
{
    use HasCamelCasing;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public const CREATED_AT = 'date_registered';
    public const UPDATED_AT = null;

    /**
     * Cast attributes to their native type
     *
     * @var <string, string>
     */
    protected $casts = [
        'userId' => 'int',
        'username' => 'string',
        'password' => 'string',
        'email' => 'string',
        'url' => 'string',
        'phone' => 'string',
        'mailingAddress' => 'string',
        'billingAddress' => 'string',
        'country' => 'string',
        'locales' => 'string',
        'gossip' => 'string',
        'dateLastEmail' => 'datetime',
        'dateRegistered' => 'datetime',
        'dateValidated' => 'datetime',
        'dateLastLogin' => 'datetime',
        'mustChangePassword' => 'boolean',
        'authId' => 'int',
        'authStr' => 'string',
        'disabled' => 'int',
        'disabled_reason' => 'string',
        'inlineHelp' => 'boolean',
        'rememberToken' => 'string',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'rememberToken',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [];

    /**
     * Many to many relationship with user groups
     */
    public function userGroups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroupModel::class, 'user_user_groups', 'user_id', 'user_group_id');
    }

    public function scopeByRoleIds(Builder $query, array $roleIds): void
    {

    }

    public function scopeWithContextId(Builder $query, array $contextIds): void
    {

    }


}
