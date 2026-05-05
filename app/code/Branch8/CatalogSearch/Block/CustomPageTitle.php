<?php

namespace Branch8\CatalogSearch\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;

class CustomPageTitle extends \Magento\Framework\View\Element\Template
{
    private $_queryFactory;

    protected $_storeManager;
    public function __construct(
        Context $context,
        QueryFactory $queryFactory,
        StoreManagerInterface $storeManager,
        array $data = []
    ){
        parent::__construct($context, $data);
        $this->_queryFactory = $queryFactory;
        $this->_storeManager = $storeManager;
        $query = $this->_queryFactory->get();

        $storeId = $this->_storeManager->getStore()->getId();
        $query->setStoreId($storeId);
        $queryText = $query->getQueryText();
        $this->pageConfig->getTitle()->set($queryText.' 熱銷推薦');
    }


}