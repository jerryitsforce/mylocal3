<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       25/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussionCustomer\Block\Product;

use Branch8\MarketPlaceProductDiscussion\Api\ProductDiscussionsQueryInterface;
use Branch8\MarketPlaceProductDiscussion\Model\Thread;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SortOrderBuilderFactory;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;

/**
 * Product Discussion Tab
 *
 * @api
 * @since 100.0.2
 */
class Discussion extends Template implements IdentityInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $_coreRegistry;

    /**
     * @param Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry                      $registry,
        array                                            $data = []
    )
    {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
        $this->setTabTitle();
    }

    /**
     * @return int|null
     */
    public function getProductId()
    {
        $product = $this->getProduct();
        return $product ? $product->getId() : null;
    }

    /**
     * @return Product
     */
    private function getProduct()
    {
        return $this->_coreRegistry->registry('product');
    }

    /**
     * Set tab title
     *
     * @return void
     */
    public function setTabTitle()
    {
        $title = __('問與答');
        $this->setTitle($title);
    }

    /**
     * @return array|string[]
     */
    public function getIdentities()
    {
        $entity = $this->getProduct();
        if (!$entity) {
            throw new \RuntimeException('Product Entity is not found in registry.');
        }
        return $this->getProduct()->getIdentities();
    }

    /**
     * @return false|string
     */
    public function getAjaxConfig()
    {
        return json_encode([
            'productId' => $this->getProductId(),
            'url' => $this->_urlBuilder->getUrl('product_discussion/ajax/loadthread'),
            'viewFull' => $this->_urlBuilder->getUrl('product_discussion/thread/list', ['sku' => $this->getProduct()->getSku()]),
            'askForQuestion' => $this->_urlBuilder->getUrl('product_discussion/thread/new',
                ['sku' => $this->getProduct()->getSku(), 'referer' => 'product']
            ),
        ]);
    }
}
