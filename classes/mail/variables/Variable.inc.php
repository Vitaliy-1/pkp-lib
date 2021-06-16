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
     * @return string[]
     * @brief maps variables with their description
     */
    abstract protected static function description() : array;

    /**
     * @return string[]
     * @brief maps variables with methods to retrieve their values
     */
    abstract protected function values() : array;

    /**
     * @param string|null $variableConst
     * @return string|string[]
     * @brief get description of all or specific variable
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
     * @param string|null $variableConst
     * @return string|string[]
     * @brief get value of all or specific variable
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
