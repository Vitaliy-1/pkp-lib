<?php

/**
 * @file classes/mail/variables/Variable.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Variable
 * @ingroup mail_variables
 *
 * @brief Represents methods to which template variable should comply
 */

namespace PKP\mail\variables;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionClassConstant;

abstract class Variable
{
    /**
     * Maps variables with their description
     * @return string[]
     */
    abstract protected static function description() : array;

    /**
     * Maps variables with methods to retrieve their values
     * @return string[]
     */
    abstract protected function values() : array;

    /**
     * Get description of all or specific variable
     * @param string|null $variableConst
     * @return string|string[]
     */
    static function getDescription(string $variableConst = null)
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
     * Get value of all or specific variable
     * @param string|null $variableConst
     * @return string|string[]
     */
    function getValue(string $variableConst = null)
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
}
