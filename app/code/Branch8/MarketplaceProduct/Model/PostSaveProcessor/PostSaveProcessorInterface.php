<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       09/02/2026
 */

namespace Branch8\MarketplaceProduct\Model\PostSaveProcessor;

use Magento\Framework\App\RequestInterface;

interface PostSaveProcessorInterface
{
    /**
     * @param RequestInterface $request
     * @param int $productId
     * @param $wholeData
     * @return mixed
     */
    public function process(RequestInterface $request,int $productId, &$wholeData = []);
}
