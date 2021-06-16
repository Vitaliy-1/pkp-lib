<?php

/**
 * @file classes/core/NotificationEvent.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NotificationEvent
 * @ingroup core
 *
 * @brief to be extended by events which supply variables to the email templates
 */

namespace PKP\core;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Testing\Fakes\EventFake;
use PKP\context\Context;
use PKP\mail\variables\ContextEmailVariable;
use PKP\mail\variables\QueuedPaymentEmailVariable;
use PKP\mail\variables\RecipientEmailVariable;
use PKP\mail\variables\ReviewAssignmentEmailVariable;
use PKP\mail\variables\SenderEmailVariable;
use PKP\mail\variables\SiteEmailVariable;
use PKP\mail\variables\StageAssignmentEmailVariable;
use PKP\mail\variables\SubmissionEmailVariable;
use PKP\payment\QueuedPayment;
use PKP\site\Site;
use PKP\stageAssignment\StageAssignment;
use PKP\submission\PKPSubmission;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\user\User;
use ReflectionClass;;
use ReflectionMethod;
use ReflectionProperty;

abstract class NotificationEvent
{

    /**
     * @return array variable name => description
     * @brief retrieves array of variables that can be assigned to email templates
     */
    public static function getVariablesDesc() : array
    {
        $properties = static::getProperties();
        $map = static::templateVariablesMap();
        $variables = [];
        foreach ($properties as $property) { /** @var  ReflectionProperty $property) */
            $class = static::getPropertyClass($property);

            // check if array is actually
            if ($class === 'array' && $property->getName() === 'recipients')
            {
                $variables = array_merge(
                    $variables,
                    $map[User::class]['recipients']::getDescription()
                );
                continue;
            }

            if (!array_key_exists($class, $map)) continue;

            // User may be associated only with a sender
            if ($class === User::class) {
                $variables = array_merge(
                    $variables,
                    $map[$class]['sender']::getDescription()
                );
                continue;
            }

            // No special treatment for others
            $variables = array_merge(
                $variables,
                $map[$class]::getDescription()
            );
        }

        return $variables;
    }

    /**
     * @param static $event
     * @return array variable name => value
     * @brief retrieves values of variables associated with the event
     */
    public static function getVariablesValues(NotificationEvent $event) : array
    {
        $properties = static::getProperties();
        $map = static::templateVariablesMap();
        $variables = [];
        foreach ($properties as $property) { /** @var  ReflectionProperty $property) */
            $class = static::getPropertyClass($property);
            $propertyName = $property->getName();

            // check if array is actually
            if ($class === 'array' && $propertyName === 'recipients')
            {
                $recipientVariable = new $map[User::class]['recipients']($event->{$propertyName});
                $variables = array_merge(
                    $variables,
                    $recipientVariable->getValue()
                );
                continue;
            }

            if (!array_key_exists($class, $map)) continue;

            // User may be associated only with a sender
            if ($class === User::class) {
                $senderVariable = new $map[$class]['sender']($event->{$propertyName});
                $variables = array_merge(
                    $variables,
                    $senderVariable->getValue()
                );
                continue;
            }

            // No special treatment for others
            $anyVariable = new $map[$class]($event->{$propertyName});
            $variables = array_merge(
                $variables,
                $anyVariable->getValue()
            );
        }

        return $variables;
    }

    /**
     * @return ReflectionMethod
     */
    protected static function getConstructor() : ReflectionMethod
    {
        $constructor = (new ReflectionClass(static::class))->getConstructor();
        if (!$constructor)
            throw new BadMethodCallException(static::class . ' requires constructor to be explicitly declared');

        return $constructor;
    }

    /**
     * @param ReflectionMethod $method
     * @return array ReflectionParameter[]
     */
    protected static function getParamsClass(ReflectionMethod $method) : array
    {
        $params = $method->getParameters();
        if (empty($params))
            throw new BadMethodCallException(static::class . ' constructor declaration requires at least one argument');

        foreach ($params as $param) {
            $type = $param->getType();
            if (!$type)
                throw new BadMethodCallException(static::class . ' constructor argument $' . $param->getName() . ' should be type hinted');
        }
        return $params;
    }

    /**
     * @return array ReflectionProperty[]
     */
    protected static function getProperties() : array
    {
        return (new ReflectionClass(static::class))->getProperties(ReflectionProperty::IS_PUBLIC);
    }

    /**
     * @param ReflectionProperty $property
     * @return string the class name of the property
     * @brief retrieves the class name of the property either type hinted or from the php docs
     */
    protected static function getPropertyClass(ReflectionProperty $property) : string
    {
        if (method_exists($property, 'getType') && $type = $property->getType()) {
            return $type->getName();
        }

        // FIXME Remove when property type hinting becomes mandatory
        $comments = $property->getDocComment();
        if (!$comments)
        {
            trigger_error($property->getName() . ' property of the ' . static::class . ' should be type hinted', E_USER_WARNING);
            return '';
        }

        preg_match('/(?<=@var)\s+([\w,\\\]+)\s+/', $comments, $matches);
        if (empty($matches))
        {
            trigger_error($property->getName() . ' property of the ' . static::class . ' should be type hinted', E_USER_WARNING);
            return '';
        }

        $potentialClassName = trim($matches[0]);

        // Return if fully qualified class name is specified; full class names can be retrieved dynamically with ::class from PHP >= 8.0
        if (class_exists($potentialClassName) && array_key_exists($potentialClassName, self::templateVariablesMap())) {
            return $potentialClassName;
        }

        // Trying to resolve class name if only unqualified one is specified
        foreach (static::templateVariablesMap() as $className => $assocVar)
        {
            if (substr($className, -strlen('\\' . $potentialClassName)) === '\\' . $potentialClassName)
            {
                return $className;
            }
        }

        return $potentialClassName;
    }

    /**
     * @return string[]
     * @brief return variables map associated with a specific object,
     * variables names should be unique
     */
    protected static function templateVariablesMap() : array
    {
        return
        [
            Site::class => SiteEmailVariable::class,
            Context::class => ContextEmailVariable::class,
            PKPSubmission::class => SubmissionEmailVariable::class,
            User::class =>
                [
                    'sender' => SenderEmailVariable::class,
                    'recipients' => RecipientEmailVariable::class,
                ],
            ReviewAssignment::class => ReviewAssignmentEmailVariable::class,
            StageAssignment::class => StageAssignmentEmailVariable::class,
            QueuedPayment::class => QueuedPaymentEmailVariable::class,
        ];
    }

    /**
     * @param ...$args
     * @brief fakes an event and supplies with arguments without triggering listeners
     * accepts the same arguments as a constructor
     */
    public static function fake(...$args)
    {
        if (!static::areFakeArgsValid($args))
            throw new BadMethodCallException('Declaration of ' . static::class . '::' . __FUNCTION__ . ' should be compatible with ' . static::class . '::__construct');

        $eventFacade = new class extends Event {
            /**
             * @param array $eventsToFake
             * @return EventFake
             * @brief override fake method to bypass call to the unregistered CacheManager
             */
            public static function fake($eventsToFake = []): EventFake
            {
                static::swap($fake = new EventFake(static::getFacadeRoot(), $eventsToFake));
                Model::setEventDispatcher($fake);
                return $fake;
            }
        };

        $eventFacade::fake();
        return new static(...$args);
    }

    /**
     * @param $args
     * @return bool true if parameters passes correspondent to the __constructor's
     */
    protected static function areFakeArgsValid($args) : bool
    {
        $constructArgs = static::getParamsClass(static::getConstructor());
        $comparedArgs = array_uintersect_assoc($args, $constructArgs, function ($a, $b) {
            $b = $b->getType()->getName();
            if (is_a($a, $b)) return 0;
            return -1;
        });

        return count($comparedArgs) === count($constructArgs);
    }
}
