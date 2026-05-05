<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Block\Adminhtml\Edit\Buttons;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
/**
 * Class GenericButton
 *
 * @api
 */
class GenericButton
{
    /**
     * Registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    private \Magento\Framework\UrlInterface $urlBuilder;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry           $registry
    )
    {
        $this->urlBuilder = $context->getUrlBuilder();
        $this->registry = $registry;
    }

    /**
     * @return ParentOrder
     */
    public function getParentOrder()
    {
        return $this->registry->registry('parent_order');
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}
