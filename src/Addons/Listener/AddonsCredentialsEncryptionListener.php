<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
declare(strict_types=1);

namespace PrestaShop\Module\Mbo\Addons\Listener;

use PrestaShop\Module\Mbo\Addons\User\CredentialsEncryptor;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

final class AddonsCredentialsEncryptionListener
{
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var CredentialsEncryptor
     */
    private $encryptor;

    public function __construct(
        SessionInterface $session,
        CredentialsEncryptor $encryptor
    ) {
        $this->session = $session;
        $this->encryptor = $encryptor;
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();

        if ($this->session->has('credentials_decrypted_before_reset')) {
            $this->session->remove('credentials_decrypted_before_reset');

            $response->headers->setCookie(
                new Cookie('username_addons_v2', $this->encryptor->encrypt($this->session->get('username_addons_v2')), -1, null, null, null, false)
            );
            $response->headers->setCookie(
                new Cookie('password_addons_v2', $this->encryptor->encrypt($this->session->get('password_addons_v2')), -1, null, null, null, false)
            );

            $this->session->remove('username_addons_v2');
            $this->session->remove('password_addons_v2');

            $event->setResponse($response);
        }
    }
}
