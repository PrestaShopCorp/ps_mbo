<?php

namespace PrestaShop\Module\Mbo\Api\Service;

interface ServiceExecutorInterface
{
    /**
     * Checks whether a class can execute the given service.
     *
     * @param string $service
     *
     * @return bool
     */
    public function canExecute(string $service): bool;

    /**
     * Executes the service with the parameters given.
     *
     * @param ...$parameters
     *
     * @return array
     */
    public function execute(...$parameters): array;
}
