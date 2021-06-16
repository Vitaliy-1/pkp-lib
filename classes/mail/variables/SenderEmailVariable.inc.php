<?php

/**
 * @file classes/mail/variables/SenderEmailVariable.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SenderEmailVariable
 * @ingroup mail_variables
 *
 * @brief Represents variables that are associated with an email sender
 */

namespace PKP\mail\variables;

use PKP\user\User;

class SenderEmailVariable extends Variable
{
    const SENDER_NAME = 'senderName';
    const SENDER_EMAIL = 'senderEmail';
    const SENDER_CONTACT_SIGNATURE = 'senderContactSignature';

    /** @var  User $sender */
    protected $sender;

    /**
     * @param User $sender
     */
    public function __construct(User $sender)
    {
        $this->sender = $sender;
    }

    /**
     * @return string[]
     * @brief see Variable description
     * TODO replace description with locale keys
     */
    protected static function description(): array
    {
        return
        [
            self::SENDER_NAME => 'The full name of a the user sending email',
            self::SENDER_EMAIL => 'Sender\'s email address',
            self::SENDER_CONTACT_SIGNATURE => 'Sender\'s contact signature, which includes full name, affiliation and phone number',
        ];
    }

    /**
     * @return array
     * @brief see Variable::values
     */
    protected function values(): array
    {
        return
        [
            self::SENDER_NAME => $this->getUserName(),
            self::SENDER_EMAIL => $this->getUserEmail(),
            self::SENDER_CONTACT_SIGNATURE => $this->getUserContactSignature(),
        ];
    }

    protected function getUserName() : string
    {
        return $this->sender->getData('fullName');
    }

    protected function getUserEmail() : string
    {
        return $this->sender->getData('email');
    }

    protected function getUserContactSignature() : string
    {
        return $this->sender->getContactSignature();
    }
}
