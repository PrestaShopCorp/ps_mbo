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

namespace PrestaShop\Module\Mbo\Modules;

use Exception;
use Module as LegacyModule;
use PaymentModule;
use PhpParser;
use Psr\Log\LoggerInterface;
use stdClass;
use Validate;

class ModuleBuilder implements ModuleBuilderInterface
{
    /**
     * @var array
     */
    public const MAIN_CLASS_ATTRIBUTES = [
        'warning',
        'name',
        'tab',
        'displayName',
        'description',
        'author',
        'author_address',
        'limited_countries',
        'need_instance',
        'confirmUninstall',
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Path to the module directory, coming from Confiuration class.
     *
     * @var string
     */
    private $moduleDirectory;

    public function __construct(
        LoggerInterface $logger,
        string $moduleDirectory
    ) {
        $this->logger = $logger;
        $this->moduleDirectory = $moduleDirectory;
    }

    public function build(stdClass $module, ?array $database = null): Module
    {
        /* Convert module to array */
        $attributes = json_decode(json_encode($module), true);

        // Get filemtime of module main class (We do this directly with an error suppressor to go faster)
        $filePath = $this->getModulePath($module->name);
        $moduleIsPresentOnDisk = file_exists($filePath);

        $disk = [
            'filemtime' => $moduleIsPresentOnDisk ? (int) filemtime($filePath) : 0,
            'is_present' => $moduleIsPresentOnDisk,
            'is_valid' => 0,
            'version' => null,
            'path' => $this->moduleDirectory . $module->name,
        ];

        if ($this->isModuleMainClassValid($module->name)) {
            $mainClassAttributes = [];

            // We load the main class of the module, and get its properties
            $tmpModule = LegacyModule::getInstanceByName($module->name);
            foreach (static::MAIN_CLASS_ATTRIBUTES as $dataToRetrieve) {
                if (isset($tmpModule->{$dataToRetrieve})) {
                    $mainClassAttributes[$dataToRetrieve] = $tmpModule->{$dataToRetrieve};
                }
            }

            $mainClassAttributes['parent_class'] = get_parent_class($module->name);
            $mainClassAttributes['is_paymentModule'] = is_subclass_of($module->name, PaymentModule::class);
            $mainClassAttributes['is_configurable'] = (int) method_exists($tmpModule, 'getContent');

            $disk['is_valid'] = 1;
            $disk['version'] = $tmpModule->version;
            $attributes = array_merge($attributes, $mainClassAttributes);
        }

        return new Module($attributes, $disk, $database);
    }

    /**
     * We won't load an invalid class. This function will check any potential parse error.
     *
     * @param string $name The technical module name to check
     *
     * @return bool true if valid
     */
    protected function isModuleMainClassValid(string $name): bool
    {
        if (!Validate::isModuleName($name)) {
            return false;
        }

        $filePath = $this->getModulePath($name);
        // Check if file exists (slightly faster than file_exists)
        if (!file_exists($filePath)) {
            return false;
        }

        $parser = (new PhpParser\ParserFactory())->create(PhpParser\ParserFactory::ONLY_PHP7);
        $logContextData = [
            'object_type' => 'Module',
            'object_id' => LegacyModule::getModuleIdByName($name),
        ];

        try {
            $parser->parse(file_get_contents($filePath));
        } catch (PhpParser\Error $exception) {
            $this->logger->critical(
                sprintf(
                    'Parse error detected in main class of module %s: %s',
                    $name,
                    $exception->getMessage(),
                ),
                $logContextData
            );

            return false;
        }

        $logger = $this->logger;

        // -> Even if we do not detect any parse error in the file, we may have issues
        // when trying to load the file. (i.e with additional require_once).
        // -> We use an anonymous function here because if a test is made twice
        // on the same module, the test on require_once would immediately return true
        // (as the file would have already been evaluated).
        $requireCorrect = function ($name) use ($filePath, $logger, $logContextData) {
            try {
                require_once $filePath;
            } catch (Exception $e) {
                $logger->error(
                    sprintf(
                        'Error while loading file of module %module%. %error_message%',
                        $name,
                        $e->getMessage(),
                    ),
                    $logContextData
                );

                return false;
            }

            return true;
        };

        return $requireCorrect($name);
    }

    protected function getModulePath(string $name): string
    {
        return $this->moduleDirectory . $name . '/' . $name . '.php';
    }
}
