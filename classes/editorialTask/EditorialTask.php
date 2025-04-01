<?php

/**
 * @file classes/editorialTask/EditorialTask.php
 *
 * Copyright (c) 2024 Simon Fraser University
 * Copyright (c) 2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class EditorialTask
 *
 * @brief Class for editorial tasks and discussions.
 */

namespace PKP\editorialTask;

use APP\facades\Repo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use PKP\core\traits\ModelWithSettings;
use PKP\note\Note;
use PKP\user\User;

class EditorialTask extends Model
{
    use ModelWithSettings;

    // Type of the editorial task
    public const TYPE_DISCUSSION = 1;
    public const TYPE_TASK = 2;

    // Current status of the editorial task
    public const STATUS_NEW = 1;
    public const STATUS_REPLIED = 2;
    public const STATUS_CLOSED = 3;

    // Allow filling and saving related model through 'participants' Model attribute
    public const ATTRIBUTE_PARTICIPANTS = 'participants';

    protected $table = 'edit_tasks';
    protected $primaryKey = 'edit_task_id';

    protected $guarded = ['edit_tasks_id', 'id'];

    protected $fillable = [
        'assocType', 'assocId', 'stageId', 'seq',
        'createdAt', 'updatedAt', 'closed', 'dateDue',
        'createdBy', 'type', 'status'
    ];

    /**
     * @var int[] $participantIds
     */
    protected array $participantIds = [];

    protected function casts(): array
    {
        return [
            'assocType' => 'int',
            'assocId' => 'int',
            'stageId' => 'int',
            'seq' => 'float',
            'createdAt' => 'datetime',
            'updatedAt' => 'datetime',
            'closed' => 'boolean',
            'dateDue' => 'datetime',
            'createdBy' => 'int',
            'type' => 'int',
            'status' => 'int',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getSettingsTable(): string
    {
        return 'edit_task_settings';
    }

    /**
     * @inheritDoc
     */
    public static function getSchemaName(): ?string
    {
        return null;
    }

    /**
     * Override Eloquent Model's method to accept participant IDs as an attribute
     */
    public function fill(array $attributes)
    {
        $this->participantIds = $attributes[self::ATTRIBUTE_PARTICIPANTS] ?? [];
        unset($attributes[self::ATTRIBUTE_PARTICIPANTS]);
        return parent::fill($attributes);
    }

    /**
     * Override Eloquent Model's method to save participants associated with the Tasks and Discussion
     */
    public function save(array $options = []): bool
    {
        $success = parent::save($options);
        if (!$success) {
            return false;
        }

        // TODO save the responsible property of the participant
        $participants = Repo::user()->getCollector()
            ->filterByUserIds($this->participantIds)
            ->getMany()
            ->map(fn (User $user) => new Participant(['user_id' => $user->getId()]))
            ->all();

        $this->participants()->saveMany($participants);
        return $success;
    }

    /**
     * Accessor and Mutator for primary key => id
     */
    protected function id(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes[$this->primaryKey] ?? null,
            set: fn ($value) => [$this->primaryKey => $value],
        );
    }

    /**
     * Accessor for users. Can be replaced with relationship once User is converted to an Eloquent Model.
     */
    protected function users(): Attribute
    {
        return Attribute::make(
            get: function () {
                $userIds = $this->participants()
                    ->pluck('user_id')
                    ->all();
                return Repo::user()->getCollector()->filterByUserIds($userIds)->getMany();
            },
        );
    }

    /**
     * Relationship to Query Participants. Can be replaced with Many-to-Many relationship once
     * User is converted to an Eloquent Model.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class, 'edit_task_id', 'edit_task_id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'assoc');
    }

    // Scopes

    /**
     * Scope a query to only include queries with a specific assoc type and assoc ID.
     */
    public function scopeWithAssoc(Builder $query, int $assocType, int $assocId): Builder
    {
        return $query->where('assoc_type', $assocType)
            ->where('assoc_id', $assocId);
    }

    /**
     * Scope a query to only include queries with a specific stage ID.
     */
    public function scopeWithStageId(Builder $query, int $stageId): Builder
    {
        return $query->where('stage_id', $stageId);
    }

    /**
     * Scope a query to only include queries with a specific closed status.
     */
    public function scopeWithClosed(Builder $query, bool $closed): Builder
    {
        return $query->where('closed', $closed);
    }

    /**
     * Scope a query to only include queries with a specific user ID.
     */
    public function scopeWithUserId(Builder $query, int $userId): Builder
    {
        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * Scope a query to only include queries with specific user IDs.
     */
    public function scopeWithUserIds($query, array $userIds)
    {
        return $query->whereHas('participants', function ($q) use ($userIds) {
            $q->whereIn('user_id', $userIds);
        });
    }
}
