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

namespace PrestaShop\Module\Mbo\Module;

use Symfony\Component\Finder\Finder;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ModuleOverrideChecker
{
    private array $overrides = [];

    private static ?ModuleOverrideChecker $instance;

    private const CUSTOM_DEV = '_CUSTOM_DEV_';
    private const PATTERNS = [
        'class' => '/class\s+(\w+)\s+extends\s+(\w+)/',
        'method' => '#(?:/\*(?:\s|\*)+module:\s+(\w+)[^/]*/\s+)?((?:public|private|protected)\s+(?:\w|\s)*function\s+(\w+))#',
        'property' => '#(?:/\*(?:\s|\*)+module:\s+(\w+)[^/]*/\s+)?((?:public|private|protected)\s+(?:\w|\s)*\$(\w+))#',
        'constant' => '#(?:/\*(?:\s|\*)+module:\s+(\w+)[^/]*/\s+)?((?:public|private|protected)?\s+(?:\w|\s)*const\s+(?:\w*\s+)*(\w+))#',
    ];

    public static function getInstance(): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function listOverridesFromPsDirectory(): array
    {
        $finder = new Finder();
        $finder->files()->in(_PS_OVERRIDE_DIR_)->name('*.php');

        if (!$finder->hasResults()) {
            return [];
        }

        $fileList = [];
        foreach ($finder as $file) {
            $fileList[] = $file->getRelativePathname();
        }

        return $this->extractOverridesFromFiles($fileList);
    }

    public function extractOverridesFromFiles(array $fileList): array
    {
        $cacheKey = sha1(implode('_', $fileList));
        if (isset($this->overrides[$cacheKey])) {
            return $this->overrides[$cacheKey];
        }

        $this->overrides[$cacheKey] = [];
        foreach ($fileList as $file) {
            $overrideFile = rtrim(_PS_OVERRIDE_DIR_, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($file, DIRECTORY_SEPARATOR);

            if (file_exists($overrideFile)) {
                $overrideFileContent = file_get_contents($overrideFile);
                $className = $this->getClassNameFromContent($overrideFileContent);

                $conflictingMethods = $this->extractConflictingElements($overrideFileContent, 'method');
                $conflictingProperties = $this->extractConflictingElements($overrideFileContent, 'property');
                $conflictingConstants = $this->extractConflictingElements($overrideFileContent, 'constant');

                if (!empty($className) && ($conflictingMethods || $conflictingProperties || $conflictingConstants)) {
                    $this->overrides[$cacheKey][$file] = [
                        'class' => [
                            'signature' => $className[0],
                            'name' => $className[1],
                            'extends' => $className[2],
                        ],
                        'methods' => $conflictingMethods,
                        'properties' => $conflictingProperties,
                        'constants' => $conflictingConstants,
                    ];
                }
            }
        }

        return $this->overrides[$cacheKey];
    }

    public function resetOverrides(): array
    {
        return $this->overrides = [];
    }

    private function getClassNameFromContent(string $content): array
    {
        $classNames = [];
        if (preg_match(self::PATTERNS['class'], $content, $matches)) {
            $classNames = $matches;
        }

        return $classNames;
    }

    private function extractConflictingElements(string $content, string $type): array
    {
        $matches = $this->getElementsFromContent($content, $type);

        if (empty($matches[1])) {
            return [];
        }

        return $this->formatMatches($matches);
    }

    private function getElementsFromContent(string $content, string $type): array
    {
        $elements = [];

        if (isset(self::PATTERNS[$type]) && preg_match_all(self::PATTERNS[$type], $content, $matches)) {
            $elements = $matches;
        }

        return $elements;
    }

    private function formatMatches(array $matches): array
    {
        $formattedOverrides = [];

        for ($i = 0; $i < count($matches[1]); ++$i) {
            $formattedOverrides[$matches[3][$i]] = [
                'name' => trim($matches[3][$i]),
                'signature' => trim($matches[2][$i]),
                'module' => empty($matches[1][$i]) ? self::CUSTOM_DEV : trim($matches[1][$i]),
            ];
        }

        return $formattedOverrides;
    }
}
