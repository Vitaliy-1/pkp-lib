<?php

/**
 * @file classes/mail/variables/StageAssignmentEmailVariable.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StageAssignmentEmailVariable
 * @ingroup mail_variables
 *
 * @brief Represents email template variables that are associated with a editor assignments
 */

namespace PKP\mail\variables;

use PKP\db\DAORegistry;
use PKP\stageAssignment\StageAssignment;
use PKP\submission\reviewAssignment\ReviewAssignment;

class StageAssignmentEmailVariable extends Variable
{
    const DECISION_MAKING_EDITORS = 'editors';

    /** @var StageAssignment $stageAssignment */
    protected $stageAssignment;

    public function __construct(StageAssignment $stageAssignment)
    {
        $this->stageAssignment = $stageAssignment;
    }

    /**
     * @return string[]
     * @brief see Variable::description()
     * TODO replace description with locale keys
     */
    protected static function description(): array
    {
        return
        [
            self::DECISION_MAKING_EDITORS => 'List of editors that are allowed to make decisions on this stage assignment',
        ];
    }

    /**
     * @return array
     * @brief see Variable::values()
     */
    protected function values(): array
    {
        return
        [
            self::DECISION_MAKING_EDITORS => $this->getEditors(),
        ];
    }

    protected function getEditors() : string
    {
        $editorsStr = '';
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $userDao = DAORegistry::getDAO('UserDAO');
        $editorsStageAssignments = $stageAssignmentDao->getEditorsAssignedToStage($this->stageAssignment->getSubmissionId(), $this->stageAssignment->getStageId());
        $i = 0;
        foreach ($editorsStageAssignments as $editorsStageAssignment) {
            if (!$editorsStageAssignment->getRecommendOnly()) {
                $editorFullName = $userDao->getUserFullName($editorsStageAssignment->getUserId());
                $editorsStr .= ($i == 0) ? $editorFullName : ', ' . $editorFullName;
                $i++;
            }
        }
        return $editorsStr;
    }
}
