<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Control\Action;

class MassActionApprove extends Action
{

    protected AuthorizationInterface $authorization;
    public function __construct(
        ContextInterface $context,
        AuthorizationInterface $authorization,
        array $components = [],
        array $data = []
    )
    {
        $this->authorization = $authorization;
        parent::__construct($context, $components, $data);
    }

    /**
     * Prepare
     *
     * @return void
     */
    public function prepare()
    {
        $componentGrid = $this->getContext()->getNamespace();
        $config = $this->getConfiguration();
        if (
            !$this->authorization->isAllowed('Branch8_MarketplaceProduct::approve')
            || $componentGrid == 'marketplacectrl_manager_products_list'
        ) {
            $config['actionDisable'] = true;
        }
        $this->setData('config', (array)$config);
        parent::prepare();
    }
}
