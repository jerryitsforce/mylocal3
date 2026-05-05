<?php

declare(strict_types=1);

namespace Branch8\CustomNotification\Ui\Component\Action;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Action;

class MassDelete extends Action
{
    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * Constructor.
     *
     * @param ContextInterface $context
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     * @param array|\JsonSerializable|null $actions
     */
    public function __construct(
        ContextInterface        $context,
        UrlInterface            $urlBuilder,
        array                   $components = [],
        array                   $data = [],
        array|\JsonSerializable $actions = null
    ) {
        parent::__construct($context, $components, $data, $actions);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @inheritdoc
     */
    public function prepare(): void
    {
        parent::prepare();
        $config = $this->getConfiguration();
        $params = ['notification_id' => $this->context->getRequestParam('id')];
        $config['url'] = $this->urlBuilder->getUrl($config['urlPath'], $params);
        $this->setData('config', $config);
    }
}
