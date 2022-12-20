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
        $request = $event->getRequest();

        if ($this->session->has('credentials_decrypted_before_change') && in_array($request->get('action'), ['reset', 'enable', 'install'])) {
            $this->session->remove('credentials_decrypted_before_change');

            $response->headers->setCookie(
                new Cookie('username_addons_v2', $this->encryptor->encrypt($this->session->get('username_addons_v2')), -1, null, null, null, false)
            );
            $response->headers->setCookie(
                new Cookie('password_addons_v2', $this->encryptor->encrypt($this->session->get('password_addons_v2')), -1, null, null, null, false)
            );

            $this->session->remove('username_addons_v2');
            $this->session->remove('password_addons_v2');

            $event->setResponse($response);
        } else { // We don't do this in case of cokies change because we are sure the ones in the request are wrong
            //Remove cookies if username is not a valid email
            $cookies = $request->cookies->all();

            $addonsUsernameCookie = $cookies['username_addons_v2'] ?? null;

            if (!empty($addonsUsernameCookie)) {
                $addonsUsernameCookie = $this->encryptor->decrypt($addonsUsernameCookie);
                $usernameParts = explode('.', $addonsUsernameCookie);
                $isValid = \Validate::isEmail($addonsUsernameCookie)
                    && mb_strlen($usernameParts[array_key_last($usernameParts)]) < 5;
                // the 5 limit for the domain extension is totally arbitrary
                // We made thi check because Validate::isEmail doesn't check the length of the doain extension

                if (!$isValid) {
                    // Removes from cookies in the response
                    $response->headers->removeCookie('username_addons_v2');
                    $response->headers->removeCookie('password_addons_v2');
                    $response->headers->removeCookie('is_contributor_v2');

                    // Removes from cookies in the browser
                    $response->headers->clearCookie('username_addons_v2');
                    $response->headers->clearCookie('password_addons_v2');
                    $response->headers->clearCookie('is_contributor_v2');
                }
            }
        }
    }
}
