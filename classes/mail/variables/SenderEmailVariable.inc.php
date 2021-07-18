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

use PKP\i18n\PKPLocale;
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
     * @copydoc Variable::description()
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
     * @copydoc Variable::values()
     */
    protected function values(): array
    {
        return
        [
            self::SENDER_NAME => $this->getUserFullName(),
            self::SENDER_EMAIL => $this->getUserEmail(),
            self::SENDER_CONTACT_SIGNATURE => $this->getUserContactSignature(),
        ];
    }

    /**
     * Array of sender's full name in supported locales
     * @return array [localeKey => username]
     */
    protected function getUserFullName() : array
    {
        $fullNameLocalized = [];
        $supportedLocales = PKPLocale::getSupportedLocales();
        foreach ($supportedLocales as $localeKey => $localeValue) {
            $fullNameLocalized[$localeKey] = $this->sender->getFullName(true, false, $localeKey);
        }
        return $fullNameLocalized;
    }

    /**
     * Sender's email
     * @return string
     */
    protected function getUserEmail() : string
    {
        return $this->sender->getData('email');
    }

    /**
     * Sender's contact signature
     * @return array
     */
    protected function getUserContactSignature() : array
    {
        $supportedLocales = PKPLocale::getSupportedLocales();
        PKPLocale::requireComponents(LOCALE_COMPONENT_PKP_USER);
        $signatureLocalized = [];
        foreach ($supportedLocales as $localeKey => $localeValue) {
            $signature = htmlspecialchars($this->sender->getFullName(true, false, $localeKey));
            if ($a = $this->sender->getData('affiliation', $localeKey)) {
                $signature .= '<br/>' . htmlspecialchars($a);
            }
            if ($p = $this->sender->getPhone()) {
                $signature .= '<br/>' . __('user.phone') . ' ' . htmlspecialchars($p);
            }
            $signature .= '<br/>' . htmlspecialchars($this->sender->getEmail());
            $signatureLocalized[$localeKey] = $signature;
        }

        return $signatureLocalized;
    }
}
