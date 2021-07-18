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
use PKP\core\Dispatcher;
use PKP\core\PKPApplication;
use PKP\core\PKPRequest;
use PKP\i18n\PKPLocale;

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

    /** @var PKPRequest $request */
    protected $request;

    public function __construct(Context $context)
    {
        $this->context = $context;
        $this->request = PKPApplication::get()->getRequest();
    }

    /**
     * @copydoc Variable::description()
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

    /**
     * @copydoc Variable::values()
     */
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

    /**
     * Title of a context
     * @return array
     */
    protected function getContextName() : array
    {
        return $this->context->getData('name');
    }

    /**
     * Context's URL
     * @return string
     */
    protected function getContextUrl() : string
    {
        return $this->request->getDispatcher()->url($this->request, PKPApplication::ROUTE_PAGE, $this->context->getData('urlPath'));
    }

    /**
     * Name of a person being a primary contact of a context
     * @return string
     */
    protected function getContactName() : string
    {
        return $this->context->getData('contactName');
    }

    /**
     * Signature of a person indicated as a primary contact of a context
     * @return array
     */
    protected function getPrincipalContactSignature() : array
    {
        $signature = [];
        foreach ($this->getContextName() as $localeKey => $localizedContextName) {
            $signature[$localeKey] = $this->getContactName() . "\n" . $localizedContextName;
        }
        return $signature;
    }

    /**
     * Email address
     * @return string
     */
    protected function getContactEmail() : string
    {
        return $this->context->getData('contactEmail');
    }

    /**
     * URL to the lost password page
     * @return string
     */
    protected function getPasswordLostUrl() : string
    {
        return $this->request->getDispatcher()->url($this->request, PKPApplication::ROUTE_PAGE, $this->context->getData('urlPath'), 'login', 'lostPassword');
    }

    /**
     * URL to the individual subscription page
     * @return string
     */
    protected function getIndividualSubscriptionUrl() : string
    {
        return $this->request->getDispatcher()->url($this->request, PKPApplication::ROUTE_PAGE, $this->context->getData('urlPath'), 'payments', null, null, null, 'individual');
    }

    /**
     * URL to the institutional subscription page
     * @return string
     */
    protected function getInstitutionalSubscriptionUrl() : string
    {
        return $this->request->getDispatcher()->url($this->request, PKPApplication::ROUTE_PAGE, $this->context->getData('urlPath'), 'payments', null, null, null, 'institutional');
    }

    /**
     * Signature of a person included as a primary contact for subscriptions
     * @return string
     */
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
