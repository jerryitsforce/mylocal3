<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Block\Adminhtml\Edit\Tab\Information\OrderInformation\SubOrders;

use Magento\Sales\Model\ResourceModel\Order\Item\Collection;

class Items extends \Magento\Sales\Block\Adminhtml\Items\AbstractItems
{
    private $orders;

    private $seller;

    /**
     * @return array
     * @since 100.1.0
     */
    public function getColumns()
    {
        $columns = array_key_exists('columns', $this->_data) ? $this->_data['columns'] : [];
        return $columns;
    }

    /**
     * Retrieve required options from parent
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();
    }

    /**
     * @param $type
     * @return \Magento\Framework\View\Element\AbstractBlock|\Magento\Framework\View\Element\BlockInterface|\Magento\Sales\Block\Adminhtml\Items\AbstractItems
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getItemRenderer($type)
    {
        /** @var $renderer \Magento\Sales\Block\Adminhtml\Items\AbstractItems */
        $renderer = $this->getChildBlock($type) ?: $this->getLayout()->getBlock(
            'default_order_items_renderer'
        );
        if (!$renderer instanceof \Magento\Framework\View\Element\BlockInterface) {
            throw new \RuntimeException('Renderer for type "' . $type . '" does not exist.');
        }
        $renderer->setColumnRenders(
            $this->getLayout()->getGroupChildNames('order_items', 'column')
        );

        return $renderer;
    }
}
