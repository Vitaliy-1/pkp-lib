<?php

/**
 * @file classes/core/MailServiceProvider.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MailServiceProvider
 * @ingroup core
 *
 * @brief Registers adapted version of Laravel's Mail Service Provider
 */

namespace PKP\core;

use PKP\mail\Mailer;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\MailServiceProvider as IlluminateMailService;
use InvalidArgumentException;

class MailServiceProvider extends IlluminateMailService
{
    /**
     * @return void
     * @brief Register mailer excluding markdown renderer
     */
    public function register()
    {
        $this->registerIlluminateMailer();
    }

    /**
     * @return void
     * @brief see Illuminate\Mail\MailServiceProvider::registerIlluminateMailer()
     */
    public function registerIlluminateMailer()
    {
        $this->app->singleton('mail.manager', function ($app) {
            return new class($app) extends MailManager
            {
                /**
                 * Resolve the given mailer.
                 *
                 * @param  string  $name
                 * @return \PKP\mail\Mailer
                 *
                 * @throws InvalidArgumentException
                 *
                 * @brief implement Laravel's Mailer functionality excluding associated with View,
                 * i.e., markup rendering
                 */
                protected function resolve($name)
                {
                    $config = $this->getConfig($name);

                    if (is_null($config)) {
                        throw new InvalidArgumentException("Mailer [{$name}] is not defined.");
                    }

                    // Once we have created the mailer instance we will set a container instance
                    // on the mailer. This allows us to resolve mailer classes via containers
                    // for maximum testability on said classes instead of passing Closures.
                    $mailer = new Mailer(
                        $name,
                        $this->createSwiftMailer($config),
                        $this->app['events']
                    );

                    if ($this->app->bound('queue')) {
                        $mailer->setQueue($this->app['queue']);
                    }

                    // Next we will set all of the global addresses on this mailer, which allows
                    // for easy unification of all "from" addresses as well as easy debugging
                    // of sent messages since these will be sent to a single email address.
                    foreach (['from', 'reply_to', 'to', 'return_path'] as $type) {
                        $this->setGlobalAddress($mailer, $config, $type);
                    }

                    return $mailer;
                }
            };
        });

        $this->app->bind('mailer', function ($app) {
            return $app->make('mail.manager')->mailer();
        });
    }

    /**
     * @return string[]
     * @brief see Illuminate\Mail\MailServiceProvider::provides()
     */
    public function provides()
    {
        return
            [
                'mail.manager',
                'mailer',
            ];
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\core\MailServiceProvider', '\MailServiceProvider');
}
