<?php

namespace Branch8\AdvancedPermissions\Observer\Admin\Block;

use Amasty\Rolepermissions\Helper\Data;
use Amasty\Rolepermissions\Model\ResourceModel\Rule;
use Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab\Block;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;

class SaveAfterObserver implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /** @var Data  */
    private $helper;

    /** @var Rule  */
    private $resource;

    public function __construct(
        RequestInterface $request,
        Data $helper,
        Rule $resource
    ) {
        $this->helper = $helper;
        $this->request = $request;
        $this->resource = $resource;
    }

    /**
     * Add the new block into block rule section
     *
     * @throws AlreadyExistsException
     */
    public function execute(Observer $observer): void
    {
        if ($this->request->getModuleName() == 'api') {
            return;
        }

        $rule = $this->helper->currentRule();

        /** @var \Magento\Cms\Model\Block $block */
        $block = $observer->getData('object');

        if ($block->getId() && $rule->getBlockAccessMode() == Block::MODE_SELECTED
            && !in_array($block->getId(), $rule->getBlocks())) {
            $newBlockIds = array_merge($rule->getBlocks(), [$block->getId()]);
            $rule->setData('blocks', $newBlockIds);
            $this->resource->save($rule);
        }
    }
}
