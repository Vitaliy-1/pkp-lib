<?php

/**
 * @file classes/migration/upgrade/v3_6_0/I10406_EditorialTasks.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I10406_EditorialTasks.php
 *
 * @brief Adds migration for the editorial tasks and discussions
 */

namespace PKP\migration\upgrade\v3_6_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PKP\migration\Migration;

class I10406_EditorialTasks extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('edit_tasks', function (Blueprint $table) {
            $table->comment('Contains data regarding editorial tasks and discussions.');
            $table->id('edit_task_id');
            $table->unsignedBigInteger('submission_id');
            $table->foreign('submission_id')
                ->references('submission_id')
                ->on('submissions')
                ->cascadeOnDelete();
            $table->index('submission_id');
            $table->unsignedSmallInteger('stage_id');
            $table->unsignedBigInteger('created_by');
            // TODO if user is merged with another, update the created_by field correspondingly
            $table->foreign('created_by')
                ->references('user_id')
                ->on('users');
            $table->dateTime('date_started')->nullable();
            $table->dateTime('date_completed')->nullable();
            $table->dateTime('date_due')->nullable();
            $table->boolean('discussion_closed')->default(false);
            $table->unsignedSmallInteger('type'); // 1 - task, 2 - discussion
            $table->unsignedSmallInteger('status'); // record about last activity
            $table->timestamps();
        });

        Schema::create('edit_task_settings', function (Blueprint $table) {
            $table->comment('More data about editorial tasks, including localized properties such as the name.');
            $table->id('edit_task_setting_id');
            $table->unsignedBigInteger('edit_task_id');
            $table->foreign('edit_task_id')
                ->references('edit_task_id')
                ->on('edit_tasks')
                ->cascadeOnDelete();

            $table->string('locale', 28)->default('');
            $table->string('setting_name', 255);
            $table->mediumText('setting_value')->nullable();

            $table->unique(['edit_task_id', 'locale', 'setting_name'], 'edit_task_settings_unique');
            $table->index(['edit_task_id'], 'edit_task_settings_edit_task_id');
        });

        Schema::create('edit_task_participants', function (Blueprint $table) {
            $table->comment('Table to establish the relationship between editorial tasks and users.');
            $table->id('edit_task_participant_id');
            $table->unsignedBigInteger('edit_task_id');
            $table->foreign('edit_task_id')
                ->references('edit_task_id')
                ->on('edit_tasks')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('participant_id');
            $table->foreign('participant_id')
                ->references('user_id')
                ->on('users')
                ->cascadeOnDelete();
            $table->boolean('responsible')->default(false);
            $table->unique(['edit_task_id', 'participant_id']);
        });

        Schema::create('edit_task_templates', function (Blueprint $table) {
            $table->comment('Represents templates for the editorial tasks.');
            $table->id('edit_task_template_id');
            $table->unsignedSmallInteger('stage_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('include')->nullable();
        });

        Schema::create('edit_task_template_settings', function (Blueprint $table) {
            $table->comment('includes additional and multilingual data about the editorial task templates.');
            $table->id('edit_task_template_setting_id');

            $table->unsignedBigInteger('edit_task_template_id');
            $table->foreign('edit_task_template_id')
                ->references('edit_task_template_id')
                ->on('edit_task_templates')
                ->cascadeOnDelete();

            $table->string('locale', 28)->default('');
            $table->string('setting_name', 255);
            $table->mediumText('setting_value')->nullable();

            $table->unique(['edit_task_template_id', 'locale', 'setting_name'], 'edit_task__template_settings_unique');
            $table->index(['edit_task_template_id'], 'edit_task_template_settings_edit_task_id');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('edit_tasks');
        Schema::dropIfExists('edit_task_settings');
        Schema::dropIfExists('edit_task_participants');
        Schema::dropIfExists('edit_task_templates');
        Schema::dropIfExists('edit_task_template_settings');
    }
}
