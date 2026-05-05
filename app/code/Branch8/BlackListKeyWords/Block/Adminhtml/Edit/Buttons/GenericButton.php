<?php
declare(strict_types=1);

namespace Branch8\BlackListKeyWords\Block\Adminhtml\Edit\Buttons;

/**
 * Class GenericButton
 *
 * @api
 */
class GenericButton
{
    /**
     * Registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    private \Magento\Framework\UrlInterface $urlBuilder;

    protected \Magento\Backend\Block\Widget\Context $context;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry           $registry
    )
    {
        $this->context = $context;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->registry = $registry;
    }

    /**
     * @return mixed|null
     */
    public function getThread()
    {
        return $this->registry->registry('keyword');
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}
