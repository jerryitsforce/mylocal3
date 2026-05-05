<?php

declare(strict_types=1);

namespace Branch8\Catalog\Block\Adminhtml\Product\History;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button\ButtonList;
use Magento\Backend\Block\Widget\Button\Item;
use Magento\Backend\Block\Widget\Button\ToolbarInterface;
use Magento\Backend\Block\Widget\ContainerInterface;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;

class Bulkview extends Template implements ContainerInterface
{
    /**
     * @var array|null
     */
    private ?array $historyBulkData = null;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var ButtonList
     */
    private ButtonList $buttonList;

    /**
     * @var ToolbarInterface
     */
    private ToolbarInterface $toolbar;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ButtonList $buttonList
     * @param ToolbarInterface $toolbar
     * @param SerializerInterface $serializer
     * @param array $data
     * @param JsonHelper|null $jsonHelper
     * @param DirectoryHelper|null $directoryHelper
     */
    public function __construct(
        Template\Context    $context,
        Registry            $registry,
        ButtonList          $buttonList,
        ToolbarInterface    $toolbar,
        SerializerInterface $serializer,
        array               $data = [],
        ?JsonHelper         $jsonHelper = null,
        ?DirectoryHelper    $directoryHelper = null
    ) {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
        $this->registry = $registry;
        $this->buttonList = $buttonList;
        $this->toolbar = $toolbar;
        $this->serializer = $serializer;
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
        $routePath = $this->_data['back_route_path'] ?? 'catalog/product_status/history';
        $routePath .= '/id/' . (int)$this->getRequest()->getParam('product_id');

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

    /**
     * Get current bulk data.
     *
     * @return array|null
     */
    public function getBulkData(): ?array
    {
        if ($this->historyBulkData === null) {
            $bulkData = $this->registry->registry('historyBulkData');
            if ($bulkData) {
                $this->historyBulkData = (array)$this->serializer->unserialize($bulkData);
            }
        }
        return $this->historyBulkData;
    }
}
