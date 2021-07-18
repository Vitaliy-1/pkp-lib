<?php

/**
 * @file classes/observers/listeners/ValidateRegisteredEmail.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ValidateRegisteredEmail
 * @ingroup core
 *
 * @brief Send validation email for the registered user if required by config
 */

namespace PKP\observers\listeners;

use APP\notification\NotificationManager;
use Exception;
use Illuminate\Support\Facades\Mail;
use PKP\config\Config;
use PKP\core\PKPApplication;
use PKP\core\PKPContainer;
use PKP\core\PKPServices;
use PKP\db\AccessKeyManager;
use PKP\mail\EmailTemplate;
use PKP\notification\PKPNotification;
use PKP\observers\events\UserRegisteredContext;
use PKP\observers\events\UserRegisteredSite;
use PKP\mail\mailables\ValidateEmailContext as ContextMailable;
use PKP\mail\mailables\ValidateEmailSite as SiteMailable;

class ValidateRegisteredEmail
{

    /**
     * @param \PKP\observers\events\UserRegisteredContext
     * @return void
     */
    public function handleContextRegistration(UserRegisteredContext $event) : void
    {
        $this->manageEmail($event);
    }

    /**
     * @param \PKP\observers\events\UserRegisteredSite $event
     */
    public function handleSiteRegistration(UserRegisteredSite $event) : void
    {
        $this->manageEmail($event);
    }

    /**
     * Sends mail depending on a source - context or site registration
     * @param UserRegisteredContext|UserRegisteredSite $event
     */
    private function manageEmail($event) : void
    {
        $requireValidation = Config::getVar('email', 'require_validation');
        if (!$requireValidation) return;

        $accessKeyManager = new AccessKeyManager();
        $accessKey = $accessKeyManager->createKey(
            'RegisterContext',
            $event->registeredUser->getId(),
            null,
            Config::getVar('email', 'validation_timeout')
        );

        // Create and compile email template
        $container = PKPContainer::getInstance();
        $registerTemplate = PKPServices::get('emailTemplate')->getByKey($event->context, 'USER_VALIDATE'); /** @var $registerTemplate EmailTemplate */
        $mailer = $container['mailer'];

        if (get_class($event) === UserRegisteredContext::class) {
            $mailable = new ContextMailable($event->context, [$event->registeredUser]);
            $mailable->from($event->context->getData('supportEmail'), $event->context->getData('supportEmail'));
            $templateData = [
                'activateUrl' => PKPApplication::get()->getRequest()->url($event->context->getData('urlPath'), 'user', 'activateUser', [$event->registeredUser->getData('username'), $accessKey]),
            ];
        } else {
            $mailable = new SiteMailable($event->site, [$event->registeredUser]);
            $mailable->from($event->site->getLocalizedContactEmail(), $event->site->getLocalizedContactName());
            $templateData = [
                'activateUrl' => PKPApplication::get()->getRequest()->url(null, 'user', 'activateUser', [$event->registeredUser->getData('username'), $accessKey]),
                'contextName' => $event->site->getLocalizedTitle(),
            ];
        }

        $body = $mailer->compileParams($registerTemplate->getData('body'), $templateData);
        $subject = $mailer->compileParams($registerTemplate->getData('subject'));

        // Send mail
        $mailable
            ->html($body)
            ->subject($subject)
            ->to($event->registeredUser->getData('email'));

        try {
            Mail::send($mailable);
        } catch (Exception $e) {
            $notificationMgr = new NotificationManager();
            $notificationMgr->createTrivialNotification(
                $event->registeredUser->getId(),
                PKPNotification::NOTIFICATION_TYPE_ERROR,
                ['contents' => __('email.compose.error')]
            );
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
    }

    /**
     * Maps methods with correspondent events to listen
     * @param $events
     */
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
