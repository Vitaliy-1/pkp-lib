<?php

/**
 * @file lib/pkp/pages/bodyText/index.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Route body text page requests.
 *
 * @ingroup pages_bodyText
 */

switch ($op) {
    case 'renderPdfMetadata':
        return new PKP\pages\bodyText\BodyTextHandler();
}
