<?php

declare(strict_types=1);

namespace Branch8\CustomNotification\Block\Adminhtml\Notification;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton implements ButtonProviderInterface
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * Constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param RequestInterface $request
     */
    public function __construct(
        UrlInterface     $urlBuilder,
        RequestInterface $request
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
    }

    /**
     * @inheritDoc
     */
    public function getButtonData(): array
    {
        $data = [];
        if ($this->getNotificationId()) {
            $data = [
                'label' => __('Delete All'),
                'class' => 'delete primary',
                'on_click' => 'deleteConfirm(\'' . __('Are you sure you want to do this?')
                    . '\', \'' . $this->getDeleteUrl() . '\', {"data": {}})',
                'sort_order' => 20,
            ];
        }
        return $data;
    }

    /**
     * Returns current notification ID.
     *
     * @return int
     */
    private function getNotificationId(): int
    {
        return (int)$this->request->getParam('id');
    }

    /**
     * URL to send delete requests to.
     *
     * @return string
     */
    private function getDeleteUrl(): string
    {
        return $this->urlBuilder->getUrl('*/notification_recipient/deleteAll', ['id' => $this->getNotificationId()]);
    }
}
