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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use PKP\core\SettingsScope;
use PKP\userGroup\UserGroupModel;

class UserModel extends Model
{
    use HasCamelCasing;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public const CREATED_AT = 'date_registered';
    public const UPDATED_AT = null;
    protected string $settingsTable = 'user_settings';

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
        'familyName' => 'string',
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

    protected $settings = [
        'familyName'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        //$this->fillSettings(); // fill properties from settings after main model attributes
    }

    /**
     * Many to many relationship with user groups
     */
    public function userGroups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroupModel::class, 'user_user_groups', 'user_id', 'user_group_id');
    }

    public function scopeWithRoleIds(Builder $query, array $roleIds): void
    {
        $query->with(['userGroups' => fn (BelongsToMany $query) =>
            $query->whereIn('role_id', $roleIds)
        ]);
    }

    public function scopeWithContextIds(Builder $query, array $contextIds): void
    {
        $query->with(['userGroups' => fn (BelongsToMany $query) =>
            $query->whereIn('context_id', $contextIds)
        ]);
    }

    public function getSettingsTable(): string
    {
        return $this->settingsTable;
    }

    public function getSettings(): array
    {
        return $this->getSettings();
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new SettingsScope());
    }
}
