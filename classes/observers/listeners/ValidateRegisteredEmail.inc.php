<?php

/**
 * @file classes/observers/listeners/ValidateEmail.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ValidateEmail
 * @ingroup core
 *
 * @brief Send validation email for the registered user if required by config
 */

namespace PKP\observers\listeners;

use APP\notification\NotificationManager;
use PKP\config\Config;
use PKP\db\AccessKeyManager;
use PKP\mail\MailTemplate;
use PKP\notification\PKPNotification;
use PKP\observers\events\UserRegisteredContext;
use PKP\observers\events\UserRegisteredSite;

class ValidateRegisteredEmail
{

    /**
     * @param \PKP\observers\events\UserRegisteredContext
     * @return void
     */
    public function handleContextRegistration(UserRegisteredContext $event)
    {
        $requireValidation = Config::getVar('email', 'require_validation');
        if (!$requireValidation) return;

        $accessKeyManager = new AccessKeyManager();
        $accessKey = $accessKeyManager->createKey('RegisterContext',  $event->recipient->getId(), null, Config::getVar('email', 'validation_timeout'));

        // Send email validation request to user
        $mail = new MailTemplate('USER_VALIDATE');
        $mail->setReplyTo($event->context->getData('supportEmail'), $event->context->getData('supportName'));
        $contextPath = $event->context->getPath();
        $mail->assignParams([
            'userFullName' => $user->getFullName(),
            'contextName' => $context ? $context->getLocalizedName() : $site->getLocalizedTitle(),
            'activateUrl' => $request->url($contextPath, 'user', 'activateUser', [$this->getData('username'), $accessKey])
        ]);
        $mail->addRecipient($event->recipient->getEmail(), $event->recipient->getFullName());
        if (!$mail->send()) {
            $notificationMgr = new NotificationManager();
            $notificationMgr->createTrivialNotification($event->recipient->getId(), PKPNotification::NOTIFICATION_TYPE_ERROR, ['contents' => __('email.compose.error')]);
        }
        $mail->assignParams(UserRegisteredContextMailable::getVariablesVal($event));

        unset($mail);
    }

    /**
     * Set mail from address
     *
     * @param $request PKPRequest
     * @param $mail MailTemplate
     */
    private function _setMailFrom($mail)
    {
        $site = $request->getSite();
        $context = $request->getContext();

        // Set the sender based on the current context
        if ($context && $context->getData('supportEmail')) {
            $mail->setReplyTo($context->getData('supportEmail'), $context->getData('supportName'));
        } else {
            $mail->setReplyTo($site->getLocalizedContactEmail(), $site->getLocalizedContactName());
        }
    }

    public function subscribe($events)
    {
        $events->listen(
            UserRegisteredContext::class,
            self::class . '@handleContextRegistration'
        );

        $events->listen(
            UserRegisteredSite::class,
            self::class . '@handleSiteRegistration'
        );
    }
}
