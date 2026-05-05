<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Block\Adminhtml\Ticket\Create;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class BackButton
 */
class BackButton implements ButtonProviderInterface
{
    private UrlInterface $url;

    public function __construct(
        UrlInterface $url
    )
    {
        $this->url = $url;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->getBackUrl()),
            'class' => 'back',
            'sort_order' => 10
        ];
    }

    /**
     * Get URL for back (reset) button
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->url->getUrl('helpdesk/ticket/index');
    }
}
