<?php

$config = new PrestaShop\CodingStandards\CsFixer\Config();
$config->setRules(['@PhpCsFixer']);
/** @var \Symfony\Component\Finder\Finder $finder */
$finder = $config->setUsingCache(true)->getFinder();
$finder->in(__DIR__)->exclude('vendor');

return $config;
