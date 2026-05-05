<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       18/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Controller\Adminhtml\Product\Discussions;

use Branch8\MarketPlaceProductDiscussion\Controller\Adminhtml\Product\Discussions;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;


class Index extends Discussions implements HttpGetActionInterface
{
    /**
     * Execute controller action.
     *
     * @return ResponseInterface|ResultInterface
     */
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Thread'));
        return $resultPage;
    }
}
