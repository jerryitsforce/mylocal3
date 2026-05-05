<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportAdminUi\Ui\Component\Sales\Order;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class MassAction extends \Magento\Ui\Component\MassAction
{
    protected const ACL_RESOURCE = 'Magento_Sales::mass_actions';

    protected AuthorizationInterface $authorization;

    public function __construct(
        ContextInterface       $context,
        AuthorizationInterface $authorization,
        array                  $components = [],
        array                  $data = []
    )
    {
        $this->authorization = $authorization;

        parent::__construct($context, $components, $data);
    }

    /**
     * @inheritDoc
     */
    public function prepare(): void
    {
        foreach ($this->getChildComponents() as $key => $actionComponent) {
            if ($key === 'excel_downloadOrderFile' &&
                !$this->authorization->isAllowed('Magento_Sales::sales_download_order_file')) {
                $componentConfig = $actionComponent->getConfiguration();
                $componentConfig['actionDisable'] = true;
                $actionComponent->setData('config', $componentConfig);
            }
        }
        parent::prepare();
    }
}
