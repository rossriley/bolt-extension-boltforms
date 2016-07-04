<?php

namespace Bolt\Extension\Bolt\BoltForms;

use Bolt\Extension\Bolt\BoltForms\Config\FieldMap;
use Bolt\Extension\SimpleExtension;
use Pimple as Container;
use Silex\Application;
use Swift_Mailer as SwiftMailer;
use Swift_FileSpool as SwiftFileSpool;
use Swift_Transport_SpoolTransport as SwiftTransportSpoolTransport;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * BoltForms a Symfony Forms interface for Bolt
 *
 * Copyright (c) 2014-2016 Gawain Lynch
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Gawain Lynch <gawain.lynch@gmail.com>
 * @copyright Copyright (c) 2014-2016, Gawain Lynch
 * @license   http://opensource.org/licenses/GPL-3.0 GNU Public License 3.0
 */
class BoltFormsExtension extends SimpleExtension
{
    /**
     * {@inheritdoc}
     */
    public function getServiceProviders()
    {
        return [
            $this,
            new Provider\BoltFormsServiceProvider(),
            new Provider\RecaptchaServiceProvider(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        parent::boot($app);

        $dispatcher = $this->container['dispatcher'];
        $dispatcher->addSubscriber($app['boltforms.processor']);
        $dispatcher->addListener(KernelEvents::TERMINATE, [$app['boltforms.mailer.queue'], 'flush']);
        $dispatcher->addListener('boltforms.mailer.debug', [$app['boltforms.mailer.queue'], 'flush']);
    }

    /**
     * All the non-forms config keys.
     *
     * @return string[]
     */
    public function getConfigKeys()
    {
        return [
            'csrf',
            'recaptcha',
            'templates',
            'debug',
            'uploads',
            'fieldmap',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerFrontendControllers()
    {
        $app = $this->getContainer();
        $controllers = [
            '/async/boltforms' => new Controller\Async(),
        ];

        if ($this->getConfig()['uploads']['management_controller']) {
            $url = $app['boltforms.config']->getUploads()->get('base_uri');
            $controllers[$url] = new Controller\UploadManagement();
        }

        return $controllers;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return parent::getConfig();
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return [
            'templates/legacy',
            'templates' => ['namespace' => 'BoltForms']
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions()
    {
        $app = $this->getContainer();

        return [
            'boltforms'         => [[$app['boltforms.twig'], 'twigBoltForms'], ['is_safe' => ['html'], 'is_safe_callback' => true]],
            'boltforms_uploads' => [[$app['boltforms.twig'], 'twigBoltFormsUploads'], []],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isSafe()
    {
        return true;
    }

    /**
     * Set the defaults for configuration parameters.
     *
     * {@inheritdoc}
     */
    protected function getDefaultConfig()
    {
        return [
            'csrf'      => true,
            'recaptcha' => [
                'enabled'       => false,
                'label'         => "Please enter the reCaptch text to prove you're a human",
                'public_key'    => '',
                'private_key'   => '',
                'error_message' => "The CAPTCHA wasn't entered correctly. Please try again.",
                'theme'         => 'clean',
            ],
            'templates' => [
                'ajax'       => '@BoltForms/asset/_ajax.twig',
                'css'        => '@BoltForms/asset/_css.twig',
                'email'      => '@BoltForms/email/email.twig',
                'subject'    => '@BoltForms/email/_subject.twig',
                'messages'   => '@BoltForms/feedback/_messages.twig',
                'exception'  => '@BoltForms/feedback/_exception.twig',
                'files'      => '@BoltForms/file/browser.twig',
                'form'       => '@BoltForms/form/form.twig',
                'form_theme' => '@BoltForms/form/_form_theme.twig',
                'fields'     => '@BoltForms/form/_fields.twig',
                'recaptcha'  => '@BoltForms/form/_recaptcha.twig',
                'macros'     => '@BoltForms/_macros.twig',
            ],
            'debug' => [
                'enabled' => false,
                'address' => '',
            ],
            'uploads' => [
                'enabled'               => false,
                'base_directory'        => '/tmp/',
                'filename_handling'     => 'suffix',
                'management_controller' => false,
                'base_uri'              => 'boltforms',
            ],
            'fieldmap' => [
                'email' => new FieldMap\Email(),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerNutCommands(Container $container)
    {
        return [
            new Command\MailQueueCommand($container),
        ];
    }
}
