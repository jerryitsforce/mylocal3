<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Ui\Component\Backend\Form\Field;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Sanitizer;
use Magento\Framework\View\Element\UiComponentFactory;

class FindYourOrder extends \Magento\Ui\Component\Form\Field
{
    private $urlInterface;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $url
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface       $url,
        array              $components = [],
        array              $data = []
    )
    {
        $this->urlInterface = $url;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }


    /**
     * @inheritdoc
     */
    public function prepare()
    {
        parent::prepare();
        $this->_data['config']['searchUrl'] = $this->urlInterface->getUrl('helpdesk/ticket/LoadRecentOrders');
        $this->_data['config']['template'] ='ui/form/field';
    }
}
