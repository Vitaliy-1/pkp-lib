<?php

/**
 * @file classes/mail/variables/GeneralEmailVariable.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GeneralEmailVariable
 * @ingroup mail_variables
 *
 * @brief Represents variables that are associated with a request and are assigned to all templates
 */

namespace PKP\mail\variables;

use PKP\context\Context;
use PKP\core\PKPApplication;
use PKP\core\PKPRequest;
use PKP\site\Site;
use PKP\user\User;

class GeneralEmailVariable implements VariableInterface
{
    const CONTEXT_NAME = 'contextName';
    const CONTEXT_URL = 'contextUrl';
    const CONTACT_NAME = 'contactName';
    const PRINCIPAL_CONTACT_SIGNATURE = 'principalContactSignature';
    const CONTACT_EMAIL = 'contactEmail';
    const PASSWORD_LOST_URL = 'passwordLostUrl';
    const PASSWORD_RESET_URL = 'passwordResetUrl';
    const INDIVIDUAL_SUBSCRIPTION_URL = 'individualSubscriptionUrl';
    const INSTITUTIONAL_SUBSCRIPTION_URL = 'institutionalSubscriptionUrl';
    const SUBSCRIPTION_CONTACT_SIGNATURE = 'subscriptionContactSignature';
    const SENDER_NAME = 'senderName';
    const SENDER_EMAIL = 'senderEmail';
    const SENDER_CONTACT_SIGNATURE = 'senderContactSignature';

    /** @var PKPRequest $request */
    protected $request;

    /** @var  User $sender */
    protected $sender;

    /** @var Context|null $context */
    protected $context = null;

    /** @var Site $site */
    protected $site;

    public function __construct(PKPRequest $request = null)
    {
        is_null($request) ? $this->request = PKPApplication::get()->getRequest() : $this->request = $request;
    }

    /**
     * @return string[]
     * @brief maps variables with their description
     * TODO replace description with locale keys
     */
    protected static function description() : array
    {
        $description =
            [
                self::SENDER_NAME => 'The full name of a the user sending email',
                self::SENDER_EMAIL => 'Sender\'s email address',
            ];

        $request = PKPApplication::get()->getRequest();
        if ($request->getContext())
        {
            array_merge($description, [

            ]);
        }

        return $description;
    }

    protected function getContext() : ?Context
    {
        return $this->context ?? $this->context = $this->request->getContext();
    }

    protected function getSite() : Site
    {
        return $this->site ?? $this->site = $this->request->getSite();
    }

    protected function getSender() : User
    {
        return $this->sender ?? $this->sender = $this->request->getUser();
    }
}
