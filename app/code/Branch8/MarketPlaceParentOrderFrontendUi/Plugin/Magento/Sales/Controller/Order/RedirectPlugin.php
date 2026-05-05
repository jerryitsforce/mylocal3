<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Plugin\Magento\Sales\Controller\Order;

use Branch8\MarketPlaceParentOrderFrontendUi\Model\Config;
use Magento\Framework\Controller\Result\RedirectFactory;

class RedirectPlugin
{
    private $config;
    private RedirectFactory $redirectFactory;
    private \Magento\Framework\UrlInterface $url;

    /**
     * @param RedirectFactory $redirectFactory
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param Config $config
     */
    public function __construct(
        RedirectFactory                 $redirectFactory,
        \Magento\Framework\UrlInterface $urlInterface,
        Config                          $config
    )
    {
        $this->config = $config;
        $this->url = $urlInterface;
        $this->redirectFactory = $redirectFactory;
    }

    /**
     * @param $subject
     * @param callable $process
     * @param $args
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function aroundDispatch($subject, callable $process, $args)
    {
        if ($this->config->accessSubOrderUi()) {
            return $process($args);
        }
        $noRoute = $this->url->getUrl('noroute');
        return $this->redirectFactory->create()->setPath(
            \Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT
        )->setPath($noRoute);
    }
}
