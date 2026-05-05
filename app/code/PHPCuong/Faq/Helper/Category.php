<?php

/**
 *
 * @Author              Ngo Quang Cuong <bestearnmoney87@gmail.com>
 * @Date                2016-12-20 23:49:42
 * @Last modified by:   nquangcuong
 * @Last Modified time: 2017-01-05 09:04:25
 */

namespace PHPCuong\Faq\Helper;

use Magento\Cms\Model\Template\FilterProvider;

/**
 * Category Helper
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class Category extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \PHPCuong\Faq\Model\ResourceModel\Faqcat
     */
    protected $_faqCatResourceModel;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $filterProvider;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \PHPCuong\Faq\Model\ResourceModel\Faqcat $faqCatResourceModel
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \PHPCuong\Faq\Model\ResourceModel\Faqcat $faqCatResourceModel,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        FilterProvider $filterProvider
    ) {
        $this->_faqCatResourceModel     = $faqCatResourceModel;
        $this->_storeManager    = $storeManager;
        $this->filterProvider = $filterProvider;
        parent::__construct($context);
    }

    /**
     * Get the list of categories
     *
     * @return array|null
     */
    public function getCategoriesList()
    {
        return $this->_faqCatResourceModel->getCategoriesList();
    }


    /**
     * Filter provider
     *
     * @param string $content
     * @return string
     */
    public function filterProvider($content)
    {
        return $this->filterProvider->getBlockFilter()
            ->setStoreId($this->_storeManager->getStore()->getId())
            ->filter($content);
    }
}
