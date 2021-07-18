<?php

/**
 * @file classes/observers/listeners/ValidateEmailContext.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ValidateEmailContext
 * @ingroup mail_mailables
 *
 * @brief Send validation email for the registered user from a context if required by config
 */

namespace PKP\mail\mailables;

use PKP\context\Context;
use PKP\mail\Mailable;

class ValidateEmailContext extends Mailable
{
    public function __construct(Context $context, array $recipients)
    {
        parent::__construct(func_get_args());
    }
}
