<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder\Creditnemo;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;

class Items extends \Magento\Sales\Block\Items\AbstractItems
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current order model instance
     *
     * @return ParentOrder
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_parent_order');
    }

    /**
     * Get CreditMemo Print Url
     *
     * @param object $creditmemo
     * @return string
     */
    public function getPrintCreditmemoUrl($creditmemo)
    {
        return $this->getUrl('*/order/printCreditmemo', ['creditmemo_id' => $creditmemo->getId()]);
    }

    /**
     * Get PrintAll CreditMemos Url
     *
     * @param object $order
     * @return string
     */
    public function getPrintAllCreditmemosUrl($order)
    {
        return $this->getUrl('*/*/printCreditmemo', ['id' => $order->getId()]);
    }

    /**
     * Get creditmemo totals block html
     *
     * @param   \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return  string
     */
    public function getTotalsHtml($creditmemo)
    {
        $totals = $this->getChildBlock('creditmemo_totals');
        $html = '';
        if ($totals) {
            $totals->setCreditmemo($creditmemo);
            $html = $totals->toHtml();
        }
        return $html;
    }

    /**
     * Get html of creditmemo comments block
     *
     * @param   \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return  string
     */
    public function getCommentsHtml($creditmemo)
    {
        $html = '';
        $comments = $this->getChildBlock('creditmemo_comments');
        if ($comments) {
            $comments->setEntity($creditmemo)->setTitle($this->escapeHtmlAttr(__('About Your Refund')));
            $html = $comments->toHtml();
        }
        return $html;
    }
}
