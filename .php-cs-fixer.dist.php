<?php

$config = new PrestaShop\CodingStandards\CsFixer\Config();
$config->setRules(['trailing_comma_in_multiline' => false]);
/** @var \Symfony\Component\Finder\Finder $finder */
$finder = $config->setUsingCache(true)->getFinder();
$finder->in(__DIR__)->exclude('vendor');

return $config;
