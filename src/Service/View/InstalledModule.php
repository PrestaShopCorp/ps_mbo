<?php

namespace PrestaShop\Module\Mbo\Service\View;

class InstalledModule
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $status;
    /**
     * @var string
     */
    private $version;
    /**
     * @var string
     */
    private $configUrl;

    public function __construct(
        int $id,
        string $name,
        string $status,
        string $version,
        ?string $configUrl = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->status = $status;
        $this->version = $version;
        $this->configUrl = $configUrl;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'version' => $this->version,
            'config_url' => $this->configUrl,
        ];
    }
}
