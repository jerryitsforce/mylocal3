<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Customer\Block\BrowsingHistory;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\DB\Select;
use Magento\Framework\Url\EncoderInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Reports\Model\Event;

class Index extends \Magento\Reports\Block\Product\Widget\Viewed
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var EncoderInterface|null
     */
    private $urlEncoder;
    private HttpContext $httpContext;

    /**
     * Index constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param CustomerSession $customerSession
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     * @param \Magento\Reports\Model\Product\Index\Factory $indexFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        CustomerSession $customerSession,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Reports\Model\Product\Index\Factory $indexFactory,
        EncoderInterface $urlEncoder,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
        $this->urlEncoder = $urlEncoder;
        parent::__construct($context, $productVisibility, $indexFactory, $data);
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        if ($this->getTemplateType()) {
            $this->setTemplate($this->getTemplateType());
        }

        if(!$this->getCustomerId() && $this->httpContext->getValue('customer_id')) {
            $this->setCustomerId($this->httpContext->getValue('customer_id'));
        }
    }

    /**
     * Prepare data
     * @return Index|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _prepareLayout()
    {
        $page = ($this->getRequest()->getParam('p'))? $this->getRequest()->getParam('p') : 1;
        $limit = $this->getLimit() ? $this->getLimit() : 12;
        $customerId = $this->httpContext->getValue('customer_id');
        $collection = $this->getItemsCollection()->setPageSize($limit)->setCurPage($page);
        // if (!$this->getRequest()->getParam("isMobile")) {
            
        // }
        if (!$this->getLayout()->getBlock("recently.viewed.pager")) {
            $pager = $this->getLayout()->createBlock(
                'Magento\Theme\Block\Html\Pager',
                'recently.viewed.pager'
            )->setAvailableLimit(array(12 => 12, 24 => 24, 36 => 36))
                ->setIsShowPerPage(true)
                ->setCollection($collection);
            $this->setChild('pager', $pager);
        }

        if ($customerId) {
            $this->getItemsCollection()
                ->groupByAttribute('entity_id')
                ->getSelect()
                ->reset(Select::ORDER)
                ->order('idx_table.added_at DESC');
            $this->getItemsCollection()->setPageSize((int)$limit);
        } else {
            $this->getItemsCollection()->setOrder('idx_table.added_at','DESC')->setPageSize((int)$limit);
        }

        // var_dump($this->getItemsCollection()->getSelect()->__toString()); die();
    }

    /**
     * Get post parameters.
     *
     * @param Product $product
     * @return array
     */
    public function getAddToCartPostParams(Product $product)
    {
        $url = $this->getAddToCartUrl($product);
        return [
            'action' => $url,
            'data' => [
                'product' => $product->getEntityId(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlEncoder->encode($url),
            ]
        ];
    }

}

