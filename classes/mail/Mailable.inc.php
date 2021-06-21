<?php

/**
 * @file classes/mail/Mailable.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Mailable
 * @ingroup mail
 *
 * @brief Represents email message data
 */

namespace PKP\mail;

use BadMethodCallException;
use Illuminate\Mail\Mailable as IlluminateMailable;

class Mailable extends IlluminateMailable
{

    /** @var string
     * message object key in data array
     */
    const DATA_KEY_MESSAGE = 'message';

    /**
     * @var string|null $stageId
     * Workflow stage ID associated with with email, const WORKFLOW_STAGE_ID_...
     */
    protected $stageId;

    /**
     * @return string|null associated workflow stage ID
     */
    public function getStageId() : ?string
    {
        return $this->stageId;
    }

    /**
     * @param int $stageId
     * @return Mailable
     * @brief sets stage ID for this mailable
     */
    public function stageId(int $stageId)
    {
        $this->stageId = $stageId;
        return $this;
    }

    /**
     * @param string $localeKey
     * @throws BadMethodCallException
     *
     */
    public function locale($localeKey)
    {
        throw new BadMethodCallException('This method isn\'t supported, data passed to ' . static::class . ' should be already localized.');
    }

    /**
     * @param string $view HTML string with template variables
     * @param array $data variable => value; see also reserved keys DATA_KEY_...
     * @return Mailable
     * @brief unlike Illuminate Mailable, accepts html string with variables
     */
    public function view($view, array $data = [])
    {
        return parent::view($view, $data);
    }

    /**
     * @param string $view HTML string with template variables
     * @param array $data variable => value
     * @return Mailable
     * @brief doesn't support Illuminate markdown; alias of Mailable::view
     */
    public function markdown($view, array $data = [])
    {
        return $this->view($view, $data);
    }
}
