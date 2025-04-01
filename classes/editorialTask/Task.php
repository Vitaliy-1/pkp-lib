<?php

/**
 * @file classes/editorialTask/Task.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Task
 *
 * @ingroup editorialTask
 *
 * @brief Class representing an editorial tasks and discussions
 */

namespace PKP\editorialTask;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use PKP\core\traits\ModelWithSettings;

class Task extends Model
{
    use ModelWithSettings;

    protected $table = 'edit_tasks';
    protected $primaryKey = 'edit_task_id';

    protected $guarded = [
        'editTaskId',
        'id',
    ];

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
}
