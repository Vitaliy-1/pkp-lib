<?php

/**
 * @file classes/mail/variables/RecipientEmailVariable.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SenderEmailVariable
 * @ingroup mail_variables
 *
 * @brief Represents variables that are associated with an email recipient
 */

namespace PKP\mail\variables;

use InvalidArgumentException;
use PKP\user\User;

class RecipientEmailVariable extends Variable
{
    const RECIPIENT_FULL_NAME = 'userFullName';
    const RECIPIENT_USERNAME = 'username';
    const RECIPIENT_GIVEN_NAME = 'userGivenName';

    /** @var array $recipients */
    protected $recipients;

    /**
     * RecipientEmailVariable constructor.
     * @param array $recipient
     */
    public function __construct(array $recipient)
    {
        foreach ($recipient as $user)
        {
            if (!is_a($user, User::class))
                throw new InvalidArgumentException('recipient array values should be an instances or ancestors of ' . User::class . ', ' . get_class($user) . ' is given');
        }

        $this->recipients = $recipient;
    }

    /**
     * @return string[]
     * @brief see Variable::description
     * TODO replace description with locale keys
     */
    protected static function description() : array
    {
        return
        [
            self::RECIPIENT_FULL_NAME => 'Full name of each recipient separated by a comma',
            self::RECIPIENT_USERNAME => 'Username of each recipient separated by a comma',
            self::RECIPIENT_GIVEN_NAME => 'Given name of each recipient separated by a comma',
        ];
    }

    /**
     * @return array
     * @brief see Variable::values
     */
    protected function values() : array
    {
        return
        [
            self::RECIPIENT_FULL_NAME => $this->getRecipientsFullName(),
            self::RECIPIENT_USERNAME => $this->getRecipientsUserName(),
            self::RECIPIENT_GIVEN_NAME => $this->getRecipientsGivenName(),
        ];
    }

    protected function getRecipientsFullName() : string
    {
        $fullNames = '';
        $lastKey = array_key_last($this->recipients);
        foreach ($this->recipients as $key => $recipient)
        {
            $fullNames .= $recipient->getFullName();
            if ($key !== $lastKey)
            {
                $fullNames .= ', ';
            }
        }
        return $fullNames;
    }

    protected function getRecipientsUserName() : string
    {
        $userNames = '';
        $lastKey = array_key_last($this->recipients);
        foreach ($this->recipients as $key => $recipient)
        {
            $userNames .= $recipient->getData('username');
            if ($key !== $lastKey)
            {
                $userNames .= ', ';
            }
        }
        return $userNames;
    }

    protected function getRecipientsGivenName() : string
    {
        $givenNames = '';
        $lastKey = array_key_last($this->recipients);
        foreach ($this->recipients as $key => $recipient)
        {
            $givenNames .= $recipient->getLocalizedData('givenName');
            if ($key !== $lastKey)
            {
                $givenNames .= ', ';
            }
        }
        return $givenNames;
    }
}
