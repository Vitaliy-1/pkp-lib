<?php

/**
 * @file classes/mail/variables/SubmissionEmailVariable.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionEmailVariable
 * @ingroup mail_variables
 *
 * @brief Represents variables associated with a submission that can be assigned to a template
 */

namespace PKP\mail\variables;

use APP\core\Application;
use APP\core\Services;
use InvalidArgumentException;
use PKP\context\PKPSectionDAO;
use PKP\core\PKPApplication;
use PKP\db\DAORegistry;
use PKP\publication\PKPPublication;
use PKP\security\Role;
use PKP\security\UserGroup;
use PKP\security\UserGroupDAO;
use PKP\submission\PKPSubmission;

class SubmissionEmailVariable extends Variable
{
    const SUBMISSION_TITLE = 'submissionTitle';
    const SUBMISSION_ID = 'submissionId';
    const SUBMISSION_ABSTRACT = 'submissionAbstract';
    const AUTHOR_STRING = 'authorString';
    const SUBMISSION_URL = 'submissionUrl';

    /** @var PKPSubmission $submission */
    protected $submission;

    /** @var  PKPPublication $currentPublication */
    protected $currentPublication;

    /**
     * @param PKPSubmission $submission
     */
    public function __construct(PKPSubmission $submission)
    {
        $this->submission = $submission;
        $currentPublicationId = $this->submission->getData('currentPublicationId');
        $this->currentPublication = Services::get('publication')->get($currentPublicationId);
    }

    /**
     * @return string[]
     * @brief maps variables with their description
     * TODO replace description with locale keys
     */
    protected static function description() : array
    {
        return
        [
            self::SUBMISSION_TITLE => 'The title of the current publication',
            self::SUBMISSION_ID => 'Unique ID of the submission',
            self::SUBMISSION_ABSTRACT => 'Abstract of the submission',
            self::AUTHOR_STRING => 'The names of the authors separated by a comma',
            self::SUBMISSION_URL => 'The URL of the submission',
        ];
    }

    /**
     * @return string[]
     * @brief maps variables with their retrieval method
     */
    protected function values() : array
    {
        return
        [
            self::SUBMISSION_TITLE => $this->getPublicationTitle(),
            self::SUBMISSION_ID => $this->getSubmissionId(),
            self::SUBMISSION_ABSTRACT => $this->getPublicationAbstract(),
            self::AUTHOR_STRING => $this->getAuthorString(),
            self::SUBMISSION_URL => $this->getSubmissionUrl(),
        ];
    }

    /**
     * @return string
     * @brief retrieves the full title of the current publication
     */
    protected function getPublicationTitle() : string
    {
        return $this->currentPublication->getLocalizedFullTitle();
    }

    /**
     * @return int
     * @brief retrieves ID of associated submission
     */
    protected function getSubmissionId() : int
    {
        return $this->submission->getId();
    }

    /**
     * @return string
     * @brief retrieves localized abstract of the current publication
     */
    protected function getPublicationAbstract() : string
    {
        return $this->currentPublication->getLocalizedData('abstract');
    }

    /**
     * @return string
     * @brief retrieves a list of authors as a string separated by a comma
     */
    protected function getAuthorString() : string
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */
        $contextId = $this->submission->getData('contextId');
        $userGroups = $userGroupDao->getByRoleId($contextId, Role::ROLE_ID_AUTHOR)->toArray();
        return $this->currentPublication->getAuthorString($userGroups);
    }

    /**
     * @return string
     * @brief retrieves a URL to a current workflow stage of the submission
     */
    protected function getSubmissionUrl() : string
    {
        $request = Application::get()->getRequest();
        return $request->getDispatcher()->url($request, PKPApplication::ROUTE_PAGE, null, 'workflow', 'index', [$this->submission->getId(), $this->submission->getData('stageId')]);
    }
}
