<?php

namespace Branch8\MarketplaceSubAccount\Ui\Component\Action;

class MassDelete extends \Magento\Ui\Component\Action
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     * @param $actions
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\UrlInterface $urlBuilder,
        array $components = [],
        array $data = [],
        $actions = null
    ) {
        parent::__construct($context, $components, $data, $actions);
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
    }

    public function prepare()
    {
        parent::prepare();

        $config = $this->getConfiguration();

        $params = array('seller_id' => $this->request->getParam('seller_id'));

        $config['url'] = $this->urlBuilder->getUrl($config['urlPath'], $params);

        $this->setData('config', $config);
    }
}