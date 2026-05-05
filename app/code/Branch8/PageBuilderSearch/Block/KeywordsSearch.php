<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\PageBuilderSearch\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Branch8\ProductKeywords\Model\ResourceModel\Keyword\CollectionFactory;

class KeywordsSearch extends Template
{
    protected $_template = 'Branch8_PageBuilderSearch::keywordsSearchRenderer.phtml';

    /**
     * @var CollectionFactory
     */
    protected $_queryCollectionFactory;

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $queryCollectionFactory,
        array $data = []
    ) {
        $this->_queryCollectionFactory = $queryCollectionFactory;
        parent::__construct($context, $data);
    }

    public function getTotal()
    {
        return $this->_queryCollectionFactory->create()->load()->count();
    }
}
