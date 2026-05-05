<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Block\Adminhtml\Type\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{
    /**
     * @var Context
     */
    private Context $context;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getButtonData(): array
    {
        $id = (int)$this->context->getRequest()->getParam('entity_id');
        if (!$id) {
            return [];
        }

        $deleteUrl = $this->context->getUrlBuilder()->getUrl(
            'branch8_certification/type/delete',
            ['entity_id' => $id]
        );

        return [
            'label'          => __('Delete'),
            'class'          => 'delete',
            'on_click'       => sprintf(
                "deleteConfirm('%s', '%s')",
                __('Are you sure you want to delete this certification type?'),
                $deleteUrl
            ),
            'sort_order' => 20,
        ];
    }
}
