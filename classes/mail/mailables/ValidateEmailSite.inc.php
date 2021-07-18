<?php

/**
 * @file classes/observers/listeners/ValidateEmailSite.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ValidateEmailSite
 * @ingroup mail_mailables
 *
 * @brief Send validation email for the registered user from a context if required by config
 */

namespace PKP\mail\mailables;

use PKP\mail\Mailable;
use PKP\site\Site;

class ValidateEmailSite extends Mailable
{
    public function __construct(Site $site, array $recipients)
    {
        parent::__construct(func_get_args());
    }
}
