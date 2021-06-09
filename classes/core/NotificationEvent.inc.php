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
use PKP\mail\variables\SubmissionEmailVariable;
use PKP\notification\PKPNotification;
use PKP\payment\QueuedPayment;
use PKP\security\AccessKey;
use PKP\site\Site;
use PKP\stageAssignment\StageAssignment;
use PKP\submission\PKPSubmission;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\user\User;
use ReflectionClass;
use ReflectionMethod;
use Subscription;

abstract class NotificationEvent
{
    /**
     * @var array of available template variables
     */
    protected static $variablesMap;

    /**
     * @return array
     * @brief retrieves array of variables that can be assigned to email templates
     */
    public static function getVariablesMap(): array
    {
        if (isset(self::$variablesMap)) return self::$variablesMap;

        $constructArgs = static::getParamsClass(static::getConstructor());
        $map = static::templateVariablesMap();
        $variables = [];
        foreach ($constructArgs as $constructArg) {
            $class = $constructArg->getType()->getName();
            if (!array_key_exists($class, $map)) continue;
            $variables = array_merge(
                $variables,
                $map[$class]
            );
        }

        return self::$variablesMap = $variables;
    }

    /**
     * @return ReflectionMethod
     */
    protected static function getConstructor(): ReflectionMethod
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
    protected static function getParamsClass(ReflectionMethod $method): array
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
     * @return string[]
     * @brief return variables map associated with a specific object,
     * variables names should be unique
     */
    protected static function templateVariablesMap(): array
    {
        return
        [
            Site::class => SiteEmailVariable::class, // context or site, user -> sender, user vars; available by default
            Context::class => ContextEmailVariable::class,
            PKPSubmission::class => SubmissionEmailVariable::class,
            User::class =>
                [
                    'sender' => SenderEmailTemplate::class,
                    'recipient' => RecipientEmailVariable::class,
                ],
            ReviewAssignment::class => ReviewAssignmentEmailVariable::class,
            StageAssignment::class => StageAssignmentEmailVariable::class,
            PKPNotification::class => NotificationEmailVariable::class,
            AccessKey::class => AccessKeyEmailVariable::class,
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
    protected static function areFakeArgsValid($args): bool
    {
        $constructArgs = static::getParamsClass(static::getConstructor());
        $comparedArgs = array_uintersect_assoc($args, $constructArgs, function ($a, $b) {
            $b = $b->getType()->getName();
            if (is_a($a, $b)) return 0;
            return -1;
        });

        return count($comparedArgs) === count($constructArgs);
    }

    /**
     * @return array
     * @brief Return
     */
    public function getTemplateVariables(): array
    {

    }
}
