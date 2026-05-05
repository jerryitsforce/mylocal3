<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceStaging\Block\Update;

use Magento\Framework\AuthorizationInterface;
use Magento\Staging\Block\Adminhtml\Update\Entity\EntityProviderInterface;

class Upcoming extends \Magento\Framework\View\Element\Template
{
    /**
     * @var EntityProviderInterface
     */
    protected $entityProvider;

    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param EntityProviderInterface $entityProvider
     * @param AuthorizationInterface $authorization
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        EntityProviderInterface $entityProvider,
        AuthorizationInterface $authorization,
        array $data = []
    ) {
        $this->entityProvider = $entityProvider;
        $this->authorization = $authorization;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function toHtml()
    {
        if (!$this->entityProvider->getId()) {
            return '';
        }
        return $this->getChildHtml();
    }
}
