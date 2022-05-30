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

namespace PrestaShop\Module\Mbo\Api\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Link;

abstract class GenericClient
{
    /**
     * If set to false, you will not be able to catch the error
     * guzzle will show a different error message.
     *
     * @var bool
     */
    protected $catchExceptions = false;

    /**
     * Guzzle Client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Class Link in order to generate module link.
     *
     * @var Link
     */
    protected $link;

    /**
     * Api route.
     *
     * @var string
     */
    protected $route;

    /**
     * Set how long guzzle will wait a response before end it up.
     *
     * @var int
     */
    protected $timeout = 10;

    public function __construct(Client $client)
    {
        $this->setClient($client);
    }

    protected function getClient(): Client
    {
        return $this->client;
    }

    protected function getExceptionsMode(): bool
    {
        return $this->catchExceptions;
    }

    protected function getLink(): Link
    {
        return $this->link;
    }

    protected function getRoute(): string
    {
        return $this->route;
    }

    protected function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Wrapper of method post from guzzle client.
     *
     * @param array $options payload
     *
     * @return array return response or false if no response
     *
     * @throws GuzzleException
     */
    protected function post(array $options = []): array
    {
        $response = $this->getClient()->post($this->getRoute(), $options);
        $responseHandler = new ResponseApiHandler();
        $response = $responseHandler->handleResponse($response);

        return $response;
    }

    /**
     * Wrapper of method patch from guzzle client.
     *
     * @param array $options payload
     *
     * @return array return response or false if no response
     *
     * @throws GuzzleException
     */
    protected function patch(array $options = []): array
    {
        $response = $this->getClient()->patch($this->getRoute(), $options);
        $responseHandler = new ResponseApiHandler();
        $response = $responseHandler->handleResponse($response);

        return $response;
    }

    /**
     * Wrapper of method delete from guzzle client.
     *
     * @param array $options payload
     *
     * @return array return response array
     *
     * @throws GuzzleException
     */
    protected function delete(array $options = []): array
    {
        $response = $this->getClient()->delete($this->getRoute(), $options);
        $responseHandler = new ResponseApiHandler();
        $response = $responseHandler->handleResponse($response);

        return $response;
    }

    /**
     * Wrapper of method post from guzzle client.
     *
     * @param array $options payload
     *
     * @return array return response or false if no response
     *
     * @throws GuzzleException
     */
    protected function get(array $options = []): array
    {
        $response = $this->getClient()->get($this->getRoute(), $options);
        $responseHandler = new ResponseApiHandler();
        $response = $responseHandler->handleResponse($response);

        return $response;
    }

    protected function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    protected function setExceptionsMode(bool $bool): self
    {
        $this->catchExceptions = $bool;

        return $this;
    }

    protected function setLink(Link $link): self
    {
        $this->link = $link;

        return $this;
    }

    protected function setRoute(string $route): self
    {
        $this->route = $route;

        return $this;
    }

    protected function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }
}
