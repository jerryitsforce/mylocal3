<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderAddress;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\Registry;

class Info extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'Branch8_MarketPlaceParentOrderFrontendUi::parent_order/info.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var ParentOrderManagementInterface
     */
    protected $parentOrderManagement;

    /**
     * @var \Branch8\MarketPlaceParentOrder\Model\Services\FormatAddress
     */
    protected $addressRenderer;

    /**
     * @param TemplateContext $context
     * @param Registry $registry
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param \Branch8\MarketPlaceParentOrder\Model\Services\FormatAddress $addressRenderer
     * @param array $data
     */
    public function __construct(
        TemplateContext                                              $context,
        Registry                                                     $registry,
        ParentOrderManagementInterface                               $parentOrderManagement,
        \Branch8\MarketPlaceParentOrder\Model\Services\FormatAddress $addressRenderer,
        array                                                        $data = []
    )
    {
        $this->addressRenderer = $addressRenderer;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__(
                'Order # %1', $this->getOrder()->getDetail()->getIncrementId())
        );
    }

    /**
     * @return string
     */
    public function getPaymentInfoHtml()
    {
        return $this->parentOrderManagement->getPaymentHtml($this->getOrder());
    }

    /**
     * @return ParentOrder
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_parent_order');
    }

    /**
     * @param ParentOrderAddress $address
     * @return null
     */
    public function getFormattedAddress(ParentOrderAddress $address = null)
    {
        return $this->addressRenderer->getFormattedAddress($address);
    }
}
