<?php

/**
 * @file classes/mail/variables/VariableInterface.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class VariableInterface
 * @ingroup mail_variables
 *
 * @brief Represents an interface to which template variable should comply
 */

namespace PKP\mail\variables;

interface VariableInterface
{

    /**
     * @param string|null $variableConst
     * @return string|string[]
     * @brief get description of all or specific variable
     */
    static function getDescription(string $variableConst = null);

    /**
     * @param string|null $variableConst
     * @return string|string[]
     * @brief get value of all or specific variable
     */
    function getValue(string $variableConst = null);
}
