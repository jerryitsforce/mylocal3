<?php
/**
 * Manual Invoice Module Registration
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'HotaiConnected_ManualInvoice',
    __DIR__
);