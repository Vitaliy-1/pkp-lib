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
 * @brief Basic class describing users existing in the system.
 */

namespace PKP\user;

use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PKP\core\SettingsBuilder;
use PKP\identity\Identity;
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
    protected $fillable = [
        'username',
        'email',
        'phone',
        'givenName',
        'familyName',
        'affiliation',
        'password' // TODO, remove from mass assignable
    ];

    /**
     * @var array|true[] [setting name => is multilingual]
     */
    protected array $settings = [
        Identity::IDENTITY_SETTING_GIVENNAME => true,
        Identity::IDENTITY_SETTING_FAMILYNAME => true,
        'affiliation' => true,
        'preferredPublicName' => true,
        'biography' => true,
        'orcid' => true,
    ];

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

    public function scopeWithSearchFilter(Builder $query, string $searchPhrase): void
    {
        if (!strlen($searchPhrase = trim($searchPhrase))) {
            return;
        }

        // Settings where the search will be performed
        $settings = [Identity::IDENTITY_SETTING_GIVENNAME, Identity::IDENTITY_SETTING_FAMILYNAME, 'preferredPublicName', 'affiliation', 'biography', 'orcid'];
        // Break words by whitespace, trims and escapes "%" and "_"
        $words = array_map(fn (string $word) => '%' . addcslashes($word, '%_') . '%', preg_split('/\s+/u', $searchPhrase));

        $u = $this->table;
        foreach ($words as $word) {
            $query->where(
                fn ($query) => $query->whereRaw('LOWER(username) LIKE LOWER(?)', [$word])
                    ->orWhereRaw('LOWER(email) LIKE LOWER(?)', [$word])
                    ->orWhereExists(
                        fn (QueryBuilder $query) => $query->from('user_settings', 'us')
                            ->whereColumn('us.user_id', '=', $u . '.user_id')
                            ->whereIn('us.setting_name', $settings)
                            ->whereRaw('LOWER(us.setting_value) LIKE LOWER(?)', [$word])
                    )
                    ->orWhereExists(
                        fn (QueryBuilder $query) => $query->from('user_interests', 'ui')
                            ->join('controlled_vocab_entry_settings AS cves', 'ui.controlled_vocab_entry_id', '=', 'cves.controlled_vocab_entry_id')
                            ->whereColumn('ui.user_id', '=', $u . '.user_id')
                            ->whereRaw('LOWER(cves.setting_value) LIKE LOWER(?)', [$word])
                    )
            );
        }
    }

    public function getSettingsTable(): string
    {
        return $this->settingsTable;
    }

    /**
     * Get supported settings
     * TODO add a hook for plugins
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Create a new Eloquent query builder for the model that supports settings table
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new SettingsBuilder($query);
    }
}
