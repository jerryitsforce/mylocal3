<?php

namespace Branch8\CustomNotification\Block\View;

use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use Magenest\NotificationBox\Model\ResourceModel\Notification\CollectionFactory;

class Detail extends Template
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    protected $title;
    protected $data;
    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $_filterProvider;

    /**
     * Detail constructor.
     *
     * @param Template\Context $context
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param Session $session
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CollectionFactory $collectionFactory,
        \Magento\Framework\App\Http\Context $httpContext,
        Session $session,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->httpContext = $httpContext;
        $this->session = $session;
        $this->urlInterface = $urlInterface;
        $this->_filterProvider = $filterProvider;
        parent::__construct($context, $data);
    }

    /**
     * Get all Notification by Id
     *
     * @return void
     */
    public function getDetailNotification()
    {
        $id = $this->getRequest()->getParam('id');
        $collection = $this->collectionFactory->create()->addFieldToFilter('id', $id);

        if ($collection->getSize() > 0) {
            $data = $collection->getData();
            $this->title = $data[0]['name'];
            $this->data = $data;
        }
    }

    /**
     * Get notification data
     *
     * @return array|null
     */
    public function getNotifiData()
    {
        return $this->data;
    }

    /**
     * Check if the customer is logged in, if not, redirect to the login page
     *
     * @return void
     */
    public function redirectIfNotLoggedIn()
    {
        if (!$this->httpContext->getValue('customer_id')) {
            $this->session->setAfterAuthUrl($this->urlInterface->getCurrentUrl());
            $this->session->authenticate();
        }
    }

    /**
     * Prepare layout
     *
     * @return Template
     */
    public function _prepareLayout()
    {
        $this->getDetailNotification();
        $this->pageConfig->getTitle()->set(__($this->title));
        return parent::_prepareLayout();
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function _toHtml()
    {
        $this->getNotifiData();

        if (!empty($this->data) && isset($this->data[0]['content'])) {
            return $this->_filterProvider->getPageFilter()->filter($this->data[0]['content']);
        } else {
            return '';
        }
    }
}
