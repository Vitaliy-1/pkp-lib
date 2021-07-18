<?php

/**
* @file classes/mail/Mailer.inc.php
*
 * Copyright (c) 2014-2021 Simon Fraser University
* Copyright (c) 2000-2021 John Willinsky
* Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Mailer
 * @ingroup mail
*
 * @brief Represents interface to manage emails sending and view
 */

namespace PKP\mail;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Mail\Mailer as IlluminateMailer;
use InvalidArgumentException;
use PKP\i18n\PKPLocale;
use Swift_Mailer;

class Mailer extends IlluminateMailer
{
    const OPEN_TAG = '{';
    const CLOSING_TAG = '}';
    const DOLLAR_SIGN_TAG = '$';
    /**
     * @var null
     * Don't bind Laravel View Service, as it's not implemented
     */
    protected $views = null;

    /**
     * @param string $name
     * @param Swift_Mailer $swift
     * @param Dispatcher|null $events
     *
     * @brief creates new Mailer instance without binding with View
     */
    public function __construct(string $name, Swift_Mailer $swift, Dispatcher $events = null)
    {
        $this->name = $name;
        $this->swift = $swift;
        $this->events = $events;
    }

    /**
     * @param string $view
     * @param array $data variable => value, 'message' is reserved for the Laravel's Swift Message (Illuminate\Mail\Message)
     * @return string
     * @brief render email content into HTML string
     * @throws Exception
     */
    protected function renderView($view, $data) : string
    {
        if ($view instanceof Htmlable)
            // return HTML without data compiling
            return $view->toHtml();

        if (!is_string($view))
            throw new InvalidArgumentException('View must be instance of ' . Htmlable::class . ' or a string, ' . get_class($view) . ' is given');

        return $this->compileParams($view, $data);
    }

    /**
     * @param string $view text or HTML string
     * @param array $data variables with their values passes ['variable' => value]
     * @return string compiled string with substitute variables
     * @throws Exception
     */
    public function compileParams(string $view, array $data, string $locale = null) : string
    {
        $data = $this->localizeData($data, $locale);
        unset($data[Mailable::DATA_KEY_MESSAGE]); // Remove pre-set message variable

        $stack = [];
        $resultString = '';
        $variable = '';
        foreach ($template = str_split($view) as $key => $char) {
            if ($this->openTags($key, $char, $template)) {
                if (empty($stack)) {
                    $stack[] = $char;
                } else {
                    throw new Exception('Syntax error in email template markdown: opening a new variable tag without closing previous one');
                }
                continue;
            }

            if ($char === self::CLOSING_TAG && !empty($stack)) {
                array_pop($stack);
                $resultString = $this->recordVariable($resultString, $variable, $data);
                $variable = '';
                continue;
            }

            if (!empty($stack)) {
                $variable .= $char;
                continue;
            }

            $resultString .= $char;
        }

        if (!empty($stack))
            throw new Exception('Template variable tag should be closed');

        return $resultString;
    }

    /**
     * @param array $data
     * @param string|null $locale
     * @return array localized data
     */
    protected function localizeData(array $data, string $locale = null) : array
    {
        if (is_null($locale))
            $locale = PKPLocale::getLocale();

        if (!PKPLocale::isLocaleValid($locale))
            throw new InvalidArgumentException($locale . ' isn\'t recognized as a valid locale');

        return array_map(function ($varValue) use ($locale) {

            // Array values contain localized template data
            if (is_array($varValue)) {
                if (array_key_exists($locale, $varValue)) {
                    return $varValue[$locale];
                } else {
                    throw new InvalidArgumentException('Template variables doesn\'t have localization for the ' . $locale . ' locale' );
                }
            }
            return $varValue;
        }, $data);
    }

    /**
     * @param int $key number of char in a string starting from 0
     * @param string $char
     * @param array $template array of chars of a template
     * @return bool
     * @brief defines an opening tag of a variable
     */
    protected function openTags(int $key, string $char, array $template) : bool
    {
        if ($char !== self::OPEN_TAG)
            return false;
        if ($template[$key+1] === self::DOLLAR_SIGN_TAG)
            return true;
        return false;
    }

    /**
     * @param string $compiledString the port string that has been compiled already
     * @param string $potentialVariable candidate for a variable to seek in data
     * @param array $data ['variable' => value]
     * @return string string with a compiled variable
     * @throws Exception if data doesn't include the variable
     */
    protected function recordVariable(string $compiledString, string $potentialVariable, array $data) : string
    {
        $potentialVariable = trim(ltrim($potentialVariable, self::DOLLAR_SIGN_TAG)); // makes {$ variable } and similar also allowed
        if (array_key_exists($potentialVariable, $data)) {
            if (filter_var($potentialVariable, FILTER_VALIDATE_URL)) {
                return $this->handleUrl($compiledString, $potentialVariable, $data);
            } else {
                return $compiledString . $data[$potentialVariable];
            }
        }

        throw new Exception('Variable with name ' . $potentialVariable . ' is not assigned to this email template');
    }

    /**
     * @param string $compiledString the port string that has been compiled already
     * @param string $potentialVariable URL
     * @param array $data ['variable' => value]
     * @return string
     * @brief manage URLs as HTML links
     */
    protected function handleUrl(string $compiledString, string $potentialVariable, array $data) : string
    {
        $latestSymbols = substr($compiledString, -2);
        $value = $data[$potentialVariable];
        if ($latestSymbols === '=\'' || $latestSymbols === '=\"') {
            return $compiledString . $value;
        }

        return $compiledString . '<a href="' . $value . '" class="' . $potentialVariable . '-style-class">' . $value . '</a>';
    }
}
