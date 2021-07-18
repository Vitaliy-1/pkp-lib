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

use PKP\core\PKPApplication;
use APP\facades\Repo;
use PKP\i18n\PKPLocale;
use PKP\publication\PKPPublication;
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
        $this->currentPublication = Repo::publication()->get($currentPublicationId);
    }

    /**
     * @copydoc Variable::description()
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
     * @copydoc Variable::values()
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
     * Full title of the current publication
     * @return array [locale => title]
     */
    protected function getPublicationTitle() : array
    {
        $fullTitlesLocalized = [];
        $supportedLocales = PKPLocale::getSupportedLocales();
        foreach ($supportedLocales as $localeKey => $localeValue) {
            $fullTitlesLocalized[$localeKey] = $this->currentPublication->getLocalizedTitle($localeKey);
        }
        return $fullTitlesLocalized;
    }

    /**
     * ID of associated submission
     * @return int
     */
    protected function getSubmissionId() : int
    {
        return $this->submission->getId();
    }

    /**
     * Array containing abstracts of the current publication in available locales
     * @return array
     */
    protected function getPublicationAbstract() : array
    {
        return $this->currentPublication->getData('abstract');
    }

    /**
     * List of authors as a string separated by a comma
     * @return array [locale => authorNames]
     */
    protected function getAuthorString() : array
    {
        $authorStringLocalized = [];
        $authors = $this->currentPublication->getData('authors');
        $supportedLocales = PKPLocale::getSupportedLocales();
        foreach ($supportedLocales as $localeKey => $localeValue) {
            $lastKey = array_key_last($authors);
            $authorString = '';
            foreach ($authors as $key => $author) {
                $authorString .= $author->getFullName(true, false, $localeKey);
                if ($key !== $lastKey) {
                    $authorString .= ', ';
                }
            }
            $authorStringLocalized[$localeKey] = $authorString;
        }

        return $authorStringLocalized;
    }

    /**
     * URL to a current workflow stage of the submission
     * @return string
     */
    protected function getSubmissionUrl() : string
    {
        $request = PKPApplication::get()->getRequest();
        return $request->getDispatcher()->url($request, PKPApplication::ROUTE_PAGE, null, 'workflow', 'index', [$this->submission->getId(), $this->submission->getData('stageId')]);
    }
}
