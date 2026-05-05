<?php

declare(strict_types=1);

namespace Branch8\Report\Block\Adminhtml\Product\Logging;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Button\Item;
use Magento\Backend\Block\Widget\Button\ToolbarInterface;
use Magento\Backend\Block\Widget\ContainerInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class Detail extends Template implements ContainerInterface
{
    /**
     * @var ButtonList
     */
    private ButtonList $buttonList;

    /**
     * @var ToolbarInterface
     */
    private ToolbarInterface $toolbar;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param ButtonList $buttonList
     * @param ToolbarInterface $toolbar
     * @param array $data
     * @param JsonHelper|null $jsonHelper
     * @param DirectoryHelper|null $directoryHelper
     */
    public function __construct(
        Template\Context $context,
        ButtonList       $buttonList,
        ToolbarInterface $toolbar,
        array            $data = [],
        ?JsonHelper      $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
        $this->buttonList = $buttonList;
        $this->toolbar = $toolbar;
    }

    /**
     * @inheritdoc
     */
    public function addButton($buttonId, $data, $level = 0, $sortOrder = 0, $region = 'toolbar'): self
    {
        $this->buttonList->add($buttonId, $data, $level, $sortOrder, $region);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function removeButton($buttonId): self
    {
        $this->buttonList->remove($buttonId);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function updateButton($buttonId, $key, $data): self
    {
        $this->buttonList->update($buttonId, $key, $data);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function canRender(Item $item): bool
    {
        return !$item->isDeleted();
    }

    /**
     * @inheritdoc
     */
    protected function _prepareLayout(): self
    {
        $routePath = $this->_data['back_route_path'] ?? 'report/product_logging/grid';

        $productId = (int)$this->getRequest()->getParam('product_id');
        $routePath .= '/id/' . $productId;

        $this->buttonList->add(
            'back',
            [
                'label' => __('Back'),
                'onclick' => "setLocation('" . $this->_urlBuilder->getUrl($routePath) . "')",
                'class' => 'back'
            ]
        );
        $this->toolbar->pushButtons($this, $this->buttonList);
        return parent::_prepareLayout();
    }
}
