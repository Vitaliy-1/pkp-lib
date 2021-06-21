<?php

/**
 * @file classes/mail/variables/ContextEmailVariable.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContextEmailVariable
 * @ingroup mail_variables
 *
 * @brief Represents variables that are associated with a request and are assigned to all templates
 */

namespace PKP\mail\variables;

use PKP\context\Context;
use PKP\core\PKPApplication;

class ContextEmailVariable extends Variable
{
    const CONTEXT_NAME = 'contextName';
    const CONTEXT_URL = 'contextUrl';
    const CONTACT_NAME = 'contactName';
    const PRINCIPAL_CONTACT_SIGNATURE = 'principalContactSignature';
    const CONTACT_EMAIL = 'contactEmail';
    const PASSWORD_LOST_URL = 'passwordLostUrl';
    const INDIVIDUAL_SUBSCRIPTION_URL = 'individualSubscriptionUrl';
    const INSTITUTIONAL_SUBSCRIPTION_URL = 'institutionalSubscriptionUrl';
    const SUBSCRIPTION_CONTACT_SIGNATURE = 'subscriptionContactSignature';

    /** @var Context $context */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @return string[]
     * @brief maps variables with their description
     * TODO replace description with locale keys
     */
    protected static function description() : array
    {
        return
        [
            self::CONTEXT_NAME => 'Title of the context (journal or press)',
            self::CONTEXT_URL => 'Full path to the context (journal or press)',
            self::CONTACT_NAME => 'Full name of the user indicated as a primary contact for a context',
            self::PRINCIPAL_CONTACT_SIGNATURE => 'Signature of a user indicated as a primary contact for a context, it includes full name and context title',
            self::CONTACT_EMAIL => 'Email address specified in a primary contact field for a context',
            self::PASSWORD_LOST_URL => 'URL to the lost password page',
            self::INDIVIDUAL_SUBSCRIPTION_URL => 'URL to the user\'s individual subscription page',
            self::INSTITUTIONAL_SUBSCRIPTION_URL => 'URL to the institutional subscription page',
            self::SUBSCRIPTION_CONTACT_SIGNATURE => 'Contact signature derived from full name, email, phone and mail address specified in Subscription Policies form',
        ];
    }

    protected function values() : array
    {
        return
        [
            self::CONTEXT_NAME => $this->getContextName(),
            self::CONTEXT_URL => $this->getContextUrl(),
            self::CONTACT_NAME => $this->getContactName(),
            self::PRINCIPAL_CONTACT_SIGNATURE => $this->getPrincipalContactSignature(),
            self::CONTACT_EMAIL => $this->getContactEmail(),
            self::PASSWORD_LOST_URL => $this->getPasswordLostUrl(),
            self::INDIVIDUAL_SUBSCRIPTION_URL => $this->getIndividualSubscriptionUrl(),
            self::INSTITUTIONAL_SUBSCRIPTION_URL => $this->getInstitutionalSubscriptionUrl(),
            self::SUBSCRIPTION_CONTACT_SIGNATURE => $this->getSubscriptionContactSignature(),
        ];
    }

    protected function getContextName() : string
    {
        return $this->context->getLocalizedData('name');
    }

    protected function getContextUrl() : string
    {
        return PKPApplication::get()->getRequest()->url($this->context->getData('urlPath'));
    }

    protected function getContactName() : string
    {
        return $this->context->getData('contactName');
    }

    protected function getPrincipalContactSignature() : string
    {
        return $this->getContactName() . "\n" . $this->getContextName();
    }

    protected function getContactEmail() : string
    {
        return $this->context->getData('contactEmail');
    }

    protected function getPasswordLostUrl() : string
    {
        return PKPApplication::get()->getRequest()->url($this->context->getData('urlPath'), 'login', 'lostPassword');
    }

    protected function getIndividualSubscriptionUrl() : string
    {
        return PKPApplication::get()->getRequest()->url($this->context->getData('urlPath'), 'payments', null, null, null, 'individual');
    }

    protected function getInstitutionalSubscriptionUrl() : string
    {
        return PKPApplication::get()->getRequest()->url($this->context->getData('urlPath'), 'payments', null, null, null, 'institutional');
    }

    protected function getSubscriptionContactSignature() : string
    {
        $subscriptionName = $this->context->getData('subscriptionName');
        $subscriptionEmail = $this->context->getData('subscriptionEmail');
        $subscriptionPhone = $this->context->getData('subscriptionPhone');
        $subscriptionMailingAddress = $this->context->getData('subscriptionMailingAddress');
        $subscriptionContactSignature = $subscriptionName;

        if ($subscriptionMailingAddress != '') {
            $subscriptionContactSignature .= "\n" . $subscriptionMailingAddress;
        }
        if ($subscriptionPhone != '') {
            $subscriptionContactSignature .= "\n" . __('user.phone') . ': ' . $subscriptionPhone;
        }

        $subscriptionContactSignature .= "\n" . __('user.email') . ': ' . $subscriptionEmail;

        return $subscriptionContactSignature;
    }
}
