<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Block\Adminhtml\Category\Edit;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
/**
 * Class GenericButton
 */
class GenericButton
{
    private RequestInterface $request;
    private UrlInterface $builder;

    /**
     * @param UrlInterface $builder
     * @param RequestInterface $request
     */
    public function __construct(
        UrlInterface $builder,
        RequestInterface    $request
    )
    {
        $this->builder = $builder;
        $this->request = $request;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return (int)$this->request->getParam('category_id');
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
        return $this->builder->getUrl($route, $params);
    }
}
