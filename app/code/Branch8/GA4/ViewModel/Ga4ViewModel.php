<?php
/**
 * @package
 * @author      Cuong Ho <cuonghh@forixwebdesign.com>
 * @copyright   Copyright © 2021 Forix LLC. All Rights Reserved. *
 */
declare(strict_types=1);

namespace Branch8\GA4\ViewModel;

use Branch8\GA4\Model\Config;

class Ga4ViewModel implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function isEnabled()
    {
        return $this->config->isEnabled();
    }

    public function getGtmContainerId()
    {
        return $this->config->getGtmContainerId();
    }
}
