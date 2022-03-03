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

namespace PrestaShop\Module\Mbo\News;

class News
{
    /**
     * @var string
     */
    private $date;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $shortDescription;

    /**
     * @var string
     */
    private $link;

    public function __construct(
        string $date,
        string $title,
        string $shortDescription,
        string $link
    ) {
        $this->date = $date;
        $this->title = $title;
        $this->shortDescription = $shortDescription;
        $this->link = $link;
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'title' => $this->title,
            'short_desc' => $this->shortDescription,
            'link' => $this->link,
        ];
    }
}
