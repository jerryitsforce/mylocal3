<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceProduct\Ui\Component;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns;
use Magento\Framework\View\Element\UiComponentInterface;

class MassAction extends \Magento\Ui\Component\MassAction
{
    /**
     * @var \Branch8\MarketplaceProduct\Helper\Config $_config
     */
    protected $_config;

    /**
     * @var \Magento\Framework\AuthorizationInterface $_authModel
     */
    protected $authorizationModel;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param \Branch8\MarketplaceProduct\Helper\Config $config
     * @param UiComponentInterface[] $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        \Branch8\MarketplaceProduct\Helper\Config $config,
        array $components = [],
        array $data = []
    ) {
        $this->_config = $config;
        parent::__construct($context, $components, $data);
    }

    /**
     * Register component
     *
     * @return void
     */
    public function prepare()
    {
        if ($this->_config->isHideEnableProductMassAction()) {
            $config = $this->getData('config');
            if (isset($config['actions'])) {

                unset($config['actions']['massEnable']);
                $this->setData('config', $config);
            }
            if ($this->getComponent('massEnable')) {
                unset($this->components['massEnable']);
            }
        }
        if ($this->_config->isHideDisableProductMassAction()) {
            $config = $this->getData('config');
            if (isset($config['actions'])) {

                unset($config['actions']['massDisable']);
                $this->setData('config', $config);
            }
            if ($this->getComponent('massDisable')) {
                unset($this->components['massDisable']);
            }
        }
        parent::prepare();
    }
}
