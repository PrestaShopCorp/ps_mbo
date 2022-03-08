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

namespace PrestaShop\Module\Mbo\Tab;

class TabCollectionDecoderXml
{
    /**
     * @var string
     */
    protected $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $data = [];

        if (empty($this->content)) {
            return $data;
        }

        $simpleXMLElement = @simplexml_load_string($this->content);

        if (false === $simpleXMLElement
            || !isset($simpleXMLElement->tab)
        ) {
            return $data;
        }

        foreach ($simpleXMLElement->tab as $tab) {
            $tabClassName = null;
            $tabDisplayMode = 'slider_list';
            $tabRecommendedModules = [];
            foreach ($tab->attributes() as $key => $value) {
                if ('class_name' === $key) {
                    $tabClassName = (string) $value;
                }
                if ('display_type' === $key) {
                    $tabDisplayMode = (string) $value;
                }
            }
            foreach ($tab->children() as $module) {
                if (isset($module['position'], $module['name'])) {
                    $tabRecommendedModules[(int) $module['position']] = (string) $module['name'];
                }
            }
            if (!empty($tabClassName)) {
                $data[$tabClassName] = [
                    'displayMode' => $tabDisplayMode,
                    'recommendedModules' => $tabRecommendedModules,
                ];
            }
        }

        return $data;
    }
}
