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
use InvalidArgumentException;
use PKP\context\Context;
use PKP\core\PKPApplication;
use PKP\core\PKPContainer;
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
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

class Mailable extends IlluminateMailable
{

    /**
     * Message object key in data array
     * @var string
     */
    const DATA_KEY_MESSAGE = 'message';

    /**
     * Workflow stage ID associated with with email, const WORKFLOW_STAGE_ID_...
     * @var string|null $stageId
     */
    protected $stageId;

    /**
     * @param array $args passed to the ancestors' constructor
     */
    public function __construct(array $args)
    {
        $this->setTemplateVariables($args);
    }

    /**
     * @return string|null associated workflow stage ID
     */
    public function getStageId() : ?string
    {
        return $this->stageId;
    }

    /**
     * Sets stage ID for this mailable
     * @param int $stageId
     * @return Mailable
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
        throw new BadMethodCallException('This method isn\'t supported, data passed to ' . static::class .
            ' should be already localized.');
    }

    /**
     * Unlike Illuminate Mailable, accepts html string with variables
     * @param string $view HTML string with template variables
     * @param array $data variable => value; see also reserved keys DATA_KEY_...
     * @return Mailable
     */
    public function view($view, array $data = [])
    {
        return parent::view($view, $data);
    }

    /**
     * Doesn't support Illuminate markdown; alias of Mailable::view
     * @param string $view HTML string with template variables
     * @param array $data variable => value
     * @return Mailable
     */
    public function markdown($view, array $data = [])
    {
        return $this->view($view, $data);
    }

    /**
     * Mail Service expects the method to be implemented in ancestors to pass, e.g., email templates
     * in our case templates are decoupled from Mailables
     * @return $this
     */
    public function build()
    {
        return $this;
    }

    /**
     * Allow data to be passed to the subject
     * @param \Illuminate\Mail\Message $message
     * @param array|null $data
     * @return Mailable|void
     */
    protected function buildSubject($message, array $data = [])
    {
        if ($this->subject) {
            $data = array_merge($this->viewData, $data);
            $subject = PKPContainer::getInstance()['mailer']->compileParams($this->subject, $data);
        } else {
            $request = PKPApplication::get()->getRequest();
            $context = $request->getContext();
            $subject = $context ? $context->getLocalizedName() : $request->getSite()->getLocalizedContactName();
        }

        $message->subject($subject);

        return $this;
    }

    /**
     * return variables map associated with a specific object,
     * variables names should be unique
     * @return string[]
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
     * @param array $users
     * @return bool
     */
    protected static function arrayOfUsers(array $users) : bool
    {
        foreach ($users as $user) {
            if (!is_a($user, User::class))
                return false;
        }
        return true;
    }

    /**
     * Arguments are scanned to retrieve variables which can be assigned to the template of the email
     * @param array $args
     */
    protected function setTemplateVariables(array $args): void
    {
        $map = static::templateVariablesMap();
        foreach ($args as $arg) {
            // Treat array as recipients if it contains only User objects
            if (is_array($arg) && static::arrayOfUsers($arg)) {
                $recipientsVars = new $map[User::class]['recipients']($arg);
                $this->viewData = array_merge(
                    $this->viewData,
                    $recipientsVars->getValue()
                );
                continue;
            }

            // Treat User object passed to the constructor as sender
            if (is_a($arg, User::class)) {
                $senderVars = new $map[User::class]['sender']($arg);
                $this->viewData = array_merge(
                    $this->viewData,
                    $senderVars->getValue()
                );
                continue;
            }

            foreach ($map as $className => $assoc) {
                if (is_a($arg, $className)) {
                    $assocVariable = new $assoc($arg);
                    $this->viewData = array_merge(
                        $this->viewData,
                        $assocVariable->getValue()
                    );
                    continue 2;
                }
            }

            // Give up, object isn't mapped
            $type = is_object($arg) ? get_class($arg) : gettype($arg);
            throw new InvalidArgumentException($type . ' argument passed to the ' . static::class . ' constructor isn\'t associated with template variables');
        }
    }

    /**
     * Retrieves array of variables that can be assigned to email templates
     * @return array ['variableName' => description]
     */
    public static function getTemplateVarsDescription() : array
    {
        $args = static::getParamsClass(static::getConstructor());
        $map = static::templateVariablesMap();
        $variables = [];
        foreach ($args as $arg) { /** @var  ReflectionParameter $arg) */
            $class = $arg->getType()->getName();

            // check if array is actually
            if ($class === 'array') {
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
}
