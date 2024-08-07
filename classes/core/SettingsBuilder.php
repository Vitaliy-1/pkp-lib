<?php

/**
 * @file classes/core/SettingsBuilder.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SettingsBuilder
 *
 * @brief The class that extends Eloquent's builder to support settings tables for Models
 */

namespace PKP\core;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

class SettingsBuilder extends Builder
{
    /**
     * Get the hydrated models without eager loading.
     *
     * @param  array|string  $columns
     *
     * @return \Illuminate\Database\Eloquent\Model[]|static[]
     */
    public function getModels($columns = ['*'])
    {
        $rows = $this->getModelWithSettings($columns);
        return $this->model->hydrate(
            $rows->all()
        )->all();
    }

    /**
     * Update records in the database, including settings
     *
     * @return int
     */
    public function update(array $values)
    {
        // Separate Model's primary values from settings
        [$settingValues, $primaryValues] = collect($values)->partition(
            fn (array|string $value, string $key) => array_key_exists(Str::camel($key), $this->model->getSettings())
        );

        // Don't update settings if they aren't set
        if ($settingValues->isEmpty()) {
            return parent::update($primaryValues);
        }

        // TODO Eloquent transforms attributes to snake case, find and override instead of transforming here
        $settingValues = $settingValues->mapWithKeys(
            fn (array|string $value, string $key) => [Str::camel($key) => $value]
        );

        $u = $this->model->getTable();
        $us = $this->model->getSettingsTable();
        $primaryKey = $this->model->getKeyName();
        $query = $this->toBase();

        // Add table name to specify the right columns in the already existing WHERE statements
        $query->wheres = collect($query->wheres)->map(function (array $item) use ($u) {
            $item['column'] = $u . '.' . $item['column'];
            return $item;
        })->toArray();

        $sql = $this->buildUpdateSql($settingValues, $us, $query);

        // Build a query for update
        $count = $query->fromRaw($u . ', ' . $us)
            ->whereColumn($u . '.' . $primaryKey, '=', $us . '.' . $primaryKey)
            ->update(array_merge($primaryValues->toArray(), [
                $us . '.setting_value' => DB::raw($sql),
            ]));

        return $count;
    }

    /**
     * Insert the given attributes and set the ID on the model.
     * Overrides Builder's method to insert setting values for a models with
     *
     * @param  string|null  $sequence
     *
     * @return int
     */
    public function insertGetId(array $values, $sequence = null)
    {
        // Separate Model's primary values from settings
        [$settingValues, $primaryValues] = collect($values)->partition(
            fn (array|string $value, string $key) => array_key_exists(Str::camel($key), $this->model->getSettings())
        );

        if ($settingValues->isEmpty()) {
            return parent::insertGetId($values, $sequence);
        }

        $id = parent::insertGetId($primaryValues, $sequence);

        $rows = [];
        $settingValues->each(function (string|array $settingValue, string $settingName) use ($id, &$rows) {
            if ($this->isMultilingual($settingName)) {
                foreach ($settingValue as $locale => $localizedValue) {
                    $rows[] = [
                        'user_id' => $id, 'locale' => $locale, 'setting_name' => $settingName, 'setting_value' => $localizedValue
                    ];
                }
            } else {
                $rows[] = [
                    'user_id' => $id, 'setting_name' => $settingName, 'setting_value' => $settingValue
                ];
            }
        });

        DB::table($this->model->getSettingsTable())->insert($rows);

        return $id;
    }

    /*
     * Augment model with data from the settings table
     */
    protected function getModelWithSettings(array|string $columns = ['*'])
    {
        // First, get all Model columns from the main table
        $rows = $this->query->get();

        // Retrieve records from the settings table associated with the primary Model IDs
        $primaryKey = $this->model->getKeyName();
        $ids = $rows->pluck($primaryKey)->toArray();
        $settingsChunks = DB::table($this->model->getSettingsTable())
            ->whereIn($primaryKey, $ids)
            // Order data by original primary Model's IDs
            ->orderByRaw(
                'FIELD(' .
                $primaryKey .
                ',' .
                implode(',', $ids) .
                ')'
            )
            ->get()
            // Chunk records by Model IDs
            ->chunkWhile(
                fn (\stdClass $value, int $key, Collection $chunk) =>
                $value->{$primaryKey} === $chunk->last()->{$primaryKey}
            );

        // Associate settings with correspondent Model data
        $rows = $rows->map(function (stdClass $row) use ($settingsChunks, $primaryKey, $columns) {
            if ($settingsChunks->isNotEmpty()) {
                // Don't iterate through all setting rows to avoid Big O(n^2) complexity, chunks are ordered by Model's IDs
                // If Model's ID doesn't much it means it doesn't have any settings
                if ($row->{$primaryKey} === $settingsChunks->first()->first()->{$primaryKey}) {
                    $settingsChunk = $settingsChunks->shift();
                    $settingsChunk->each(function (\stdClass $settingsRow) use ($row) {
                        if ($settingsRow->locale) {
                            $row->{$settingsRow->setting_name}[$settingsRow->locale] = $settingsRow->setting_value;
                        } else {
                            $row->{$settingsRow->setting_name} = $settingsRow->setting_value;
                        }
                    });
                }
                $row = $this->filterRow($row, $columns);
            }

            return $row;
        });

        return $rows;
    }

    /**
     * If specific columns are selected to fill the Model with, iterate and filter all, which aren't specified
     * TODO Instead of iterating through all row properties, we can force to pass primary key as a mandatory column?
     */
    protected function filterRow(stdClass $row, string|array $columns = ['*']): stdClass
    {
        if ($columns == ['*']) {
            return $row;
        }

        $columns = Arr::wrap($columns);
        foreach ($row as $property) {
            if (!in_array($property, $columns)) {
                unset($row->{$property});
            }
        }

        return $row;
    }

    /**
     * @param Collection $settingValues list of setting names as keys and setting values to be updated
     * @param string $us name of the settings table
     * @param QueryBuilder $query original query associated with the Model
     *
     * @return string raw SQL statement
     *
     * Helper method to build a query to update settings with a conditional statement:
     * SET settings_value = CASE WHEN setting_name='' AND locale=''...
     */
    protected function buildUpdateSql(Collection $settingValues, string $us, QueryBuilder $query): string
    {
        $sql = 'CASE ';
        $bindings = [];
        $settingValues->each(function (array|string $settingValue, string $settingName) use (&$sql, &$bindings, $us) {
            if ($this->isMultilingual($settingName)) {
                foreach ($settingValue as $locale => $localizedValue) {
                    $sql .= 'WHEN ' . $us . '.setting_name=? AND ' . $us . '.locale=? THEN ? ';
                    $bindings = array_merge($bindings, [$settingName, $locale, $localizedValue]);
                }
            } else {
                $sql .= 'WHEN ' . $us . '.setting_name=? THEN ? ';
                $bindings = array_merge($bindings, [$settingName, $settingValue]);
            }
        });
        $sql .= 'ELSE setting_value END';

        // Fix the order of bindings in Laravel, user ID in the where statement should be the last
        $query->bindings['where'] = array_merge($bindings, $query->bindings['where']);

        return $sql;
    }

    /**
     * Checks if setting is multilingual
     */
    protected function isMultilingual(string $settingName): bool
    {
        return array_key_exists($settingName, $this->model->getSettings()) && $this->model->getSettings()[$settingName];
    }
};
