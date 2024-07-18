<?php
/**
 * @file classes/core/SettingsScope.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SettingsScope
 *
 * @ingroup core
 *
 * @brief Class that adds ability for Eloquent Models to interact with settings table
 */

namespace PKP\core;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SettingsScope implements Scope
{
    /**
     * @inheritDoc
     */
    public function apply(Builder $query, Model $model)
    {
        $query->leftJoin(
            $model->getSettingsTable(),
            $model->getTable() . '.' . $model->getKeyName(),
            '=',
            $model->getSettingsTable() . '.' . $model->getKeyName()
        );
    }
}
