<?php

/**
 * @file classes/mail/variables/QueuedPaymentEmailVariable.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class QueuedPaymentEmailVariable
 * @ingroup mail_variables
 *
 * @brief Represents email template variables that are associated with payments
 */

namespace PKP\mail\variables;

use Application;
use PKP\core\PKPServices;
use PKP\payment\QueuedPayment;

class QueuedPaymentEmailVariable extends Variable
{
    const ITEM_NAME = 'itemName';
    const ITEM_COST = 'itemCost';
    const ITEM_CURRENCY_CODE = 'itemCurrencyCode';

    /** @var QueuedPayment $queuedPayment */
    protected $queuedPayment;

    public function __construct(QueuedPayment $queuedPayment)
    {
        $this->queuedPayment = $queuedPayment;
    }

    /**
     * @return string[]
     * @brief see Validation::description()
     * TODO replace description with locale keys
     */
    protected static function description() : array
    {
        return
        [
            self::ITEM_NAME => 'Name of the payment',
            self::ITEM_COST => 'Amount of the payment',
            self::ITEM_CURRENCY_CODE => 'Currency code for the transaction (ISO 4217)',
        ];
    }

    /**
     * @return array
     * @brief see Validation::values()
     */
    protected function values() : array
    {
        return
        [
            self::ITEM_NAME => $this->getItemName(),
            self::ITEM_COST => $this->getItemCost(),
            self::ITEM_CURRENCY_CODE => $this->getItemCurrencyCode(),
        ];
    }

    protected function getItemName() : string
    {
        $context = PKPServices::get('context')->get($this->queuedPayment->getContextId());
        $paymentManager = Application::getPaymentManager($context);
        return $paymentManager->getPaymentName($this->queuedPayment);
    }

    protected function getItemCost() : float|int|string|null
    {
        return $this->queuedPayment->getAmount();
    }

    protected function getItemCurrencyCode() : ?string
    {
        return $this->queuedPayment->getCurrencyCode();
    }
}
