<?php

namespace PrestaShop\Module\Mbo\Checks;

use Exception;
use Tools;

class AbstractCheck implements CheckInterface
{
    /**
     * @var string
     */
    public $identifier;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    public function __construct(string $name, string $description)
    {
        $this->name = $name;
        $this->identifier = $this->slugify($name);
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function check(): bool
    {
        throw new Exception('Implement check() method in the check named ' . $this->getName());
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function resolve(): bool
    {
        throw new Exception('Implement resolve() method in the check named ' . $this->getName());
    }


    /**
     * Creates a slug from the provided string
     */
    public function slugify($string): string
    {
        return strtolower(str_replace(' ', '-', Tools::replaceAccentedChars($string)));
    }
}
