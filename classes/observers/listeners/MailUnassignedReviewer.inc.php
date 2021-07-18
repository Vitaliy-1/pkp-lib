<?php

/**
 * @file classes/observers/listeners/MailAssignedReviewer.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MailAssignedReviewer
 * @ingroup core
 *
 * @brief Send email to the assigned reviewer
 */

namespace PKP\observers\listeners;

use Exception;
use Illuminate\Support\Facades\Mail;
use PKP\core\PKPApplication;
use PKP\core\PKPContainer;
use PKP\core\PKPServices;
use PKP\i18n\PKPLocale;
use PKP\notification\PKPNotification;
use PKP\notification\PKPNotificationManager;
use PKP\observers\events\ReviewerUnassigned;
use PKP\mail\mailables\MailAssignedReviewer as Mailable;

class MailUnassignedReviewer
{
    /**
     * @param \PKP\observers\events\ReviewerUnassigned
     * @return void
     */
    public function handle(ReviewerUnassigned $event)
    {
        $request = PKPApplication::get()->getRequest();
        $userVars = $request->getUserVars();
        if (array_key_exists('skipEmail', $userVars))
            return;

        $user = $request->getUser();
        $context = $request->getContext();

        // Create new Mailable
        $mailable = new Mailable($context, $event->submission, $event->reviewAssignment, $user, [$event->reviewer]);

        // Compile template with a Mailer
        $container = PKPContainer::getInstance();
        $mailer = $container['mailer'];
        $emailTemplate = PKPServices::get('emailTemplate')->getByKey($event->submission->getData('contextId'), 'REVIEW_CANCEL');
        $locale = PKPLocale::getLocale();

        // Pass data to the template for compilation
        $data = array_merge(
            $mailable->viewData,
            ['reviewerName' => $mailable->viewData['userFullName']]
        );

        $body = $mailer->compileParams($userVars['personalMessage'], $data);
        $subject = $mailer->compileParams($emailTemplate->getData('subject', $locale), $data);
        $mailable
            ->html($body)
            ->subject($subject)
            ->from($user->getData('email'), $user->getFullName())
            ->to($event->reviewer->getData('email'), $event->reviewer->getFullName());

        try {
            Mail::send($mailable);
        } catch (Exception $e) {
            $notificationMgr = new PKPNotificationManager();
            $notificationMgr->createTrivialNotification(
                $user->getId(),
                PKPNotification::NOTIFICATION_TYPE_ERROR,
                ['contents' => __('email.compose.error')]
            );
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
    }
}
