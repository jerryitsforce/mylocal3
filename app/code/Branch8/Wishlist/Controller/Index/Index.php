<?php
declare(strict_types=1);

namespace Branch8\Wishlist\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\MultipleWishlist\Helper\Data as MultipleWishlistHelper;

class Index extends \Magento\MultipleWishlist\Controller\Index\Index
{
    /**
     * View page action
     * @return \Magento\Framework\App\ResponseInterface|ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /* @var MultipleWishlistHelper $helper */
        $helper = $this->_objectManager->get(MultipleWishlistHelper::class);

        if (!$helper->isMultipleEnabled()) {
            $wishlistId = $this->getRequest()->getParam('wishlist_id');

            if ($wishlistId && $wishlistId != $helper->getDefaultWishlist()->getId()) {
                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setUrl($helper->getListUrl());

                return $resultRedirect;
            } else {
                if ($this->getRequest()->isAjax()) {
                    /** @var \Magento\Framework\View\Result\Page $resultPage */
                    $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
                    $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                    $block = $resultPage->getLayout()
                        ->createBlock('\Magento\Wishlist\Block\Customer\Wishlist')
                        ->setTemplate('Magento_Wishlist::view_ajax.phtml')
                        ->toHtml();
                    $response->setData($block);
                    return $response;
                }
                $page = parent::execute();
            }
        } else {
            /** @var Page $page */
            $page = parent::execute();
            $page->getConfig()->addBodyClass('page-multiple-wishlist');
        }
        return $page;


    }
}

