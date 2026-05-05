<?php
declare(strict_types=1);

namespace Branch8\ShippingSubsidy\Api\Data;

interface ShippingSubsidyInterface
{
    /**
     * @return bool|null
     */
    public function getEnabled(): ?bool;

    /**
     * @param bool|null $enabled
     * @return self
     */
    public function setEnabled(?bool $enabled): self;

    /**
     * @return string|null
     */
    public function getMode(): ?string;

    /**
     * @param string|null $mode
     * @return self
     */
    public function setMode(?string $mode): self;

    /**
     * @return float|null
     */
    public function getHomeDelivery(): ?float;

    /**
     * @param float|null $amount
     * @return self
     */
    public function setHomeDelivery(?float $amount): self;

    /**
     * @return float|null
     */
    public function getStorePickup(): ?float;

    /**
     * @param float|null $amount
     * @return self
     */
    public function setStorePickup(?float $amount): self;
}
