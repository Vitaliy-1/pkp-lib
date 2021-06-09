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
use PKP\core\PKPApplication;
use PKP\publication\PKPPublication;
use PKP\submission\PKPSubmission;

class SubmissionEmailVariable implements VariableInterface
{
    const SUBMISSION_TITLE = 'submissionTitle';
    const SUBMISSION_ID = 'submissionId';
    const SUBMISSION_ABSTRACT = 'submissionAbstract';
    const AUTHOR_STRING = 'authorString';
    const SUBMISSION_URL = 'submissionUrl';
    const SECTION_NAME = 'sectionName';

    /** @var PKPSubmission $submission */
    protected $submission;

    /** @var  PKPPublication $currentPublication */
    protected $currentPublication;

    /**
     * SubmissionEmailVariable constructor.
     * @param PKPSubmission $submission
     */
    public function __construct(PKPSubmission $submission)
    {
        $this->submission = $submission;
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
            self::SECTION_NAME => 'The name of the section to which the submission is assigned',
        ];
    }

    /**
     * @param string|null $variableConst
     * @return string|string[]
     * @brief see VariableInterface::getDescription
     */
    public static function getDescription(string $variableConst = null)
    {
        $description = static::description();
        if (!is_null($variableConst))
        {
            if (!array_key_exists($variableConst, $description))
                throw new InvalidArgumentException('Template variable \'' . $variableConst . '\' doesn\'t exist in ' . static::class);
            return $description[$variableConst];
        }
        return $description;
    }

    /**
     * @param string|null $variableConst
     * @return string|string[] returns variable value if variable is specified as a last argument, otherwise returns all values
     */
    public function getValue(string $variableConst = null)
    {
        $values = static::values();
        if (!is_null($variableConst))
        {
            if (!array_key_exists($variableConst, $values))
                throw new InvalidArgumentException('Template variable \'' . $variableConst . '\' doesn\'t exist in ' . static::class);
            return $values[$variableConst];
        }

        return $values;
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
     * @return PKPPublication
     * @brief several Submission variables actually are associated with Publication properties
     */
    protected function getPublication() : PKPPublication
    {
        if (isset($this->currentPublication))
            return $this->currentPublication;
        $currentPublicationId = $this->submission->getData('currentPublicationId');
        return $this->currentPublication = Services::get('publication')->get($currentPublicationId);
    }

    /**
     * @return string
     * @brief retrieves the full title of the current publication
     */
    protected function getPublicationTitle() : string
    {
        return $this->getPublication()->getLocalizedData('fullTitle');
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
        return $this->getPublication()->getLocalizedData('abstract');
    }

    /**
     * @return string
     * @brief retrieves a list of authors as a string separated by a comma
     */
    protected function getAuthorString() : string
    {
        return $this->getPublication()->getData('authorsString');
    }

    /**
     * @return string
     * @brief retrieves a URL to a current workflow stage of the submission
     */
    protected function getSubmissionUrl() : string
    {
        $request = Application::get()->getRequest();
        return $request->url($request, PKPApplication::ROUTE_PAGE, null, 'workflow', 'index', [$this->submission->getId(), $this->submission->getData('stageId')]);
    }
}
