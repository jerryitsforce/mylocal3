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
use Magento\Setup\Exception;
use Magento\Wishlist\Controller\WishlistProviderInterface;

class RemoveAll extends \Magento\Wishlist\Controller\AbstractIndex
{
    /**
     * @var WishlistProviderInterface
     */
    protected $wishlistProvider;

    /**
     * @param Context $context
     * @param WishlistProviderInterface $wishlistProvider
     */
    public function __construct(
        Context $context,
        WishlistProviderInterface $wishlistProvider
    ) {
        $this->wishlistProvider = $wishlistProvider;
        parent::__construct($context);
    }

    /**
     * Remove item
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Exception
     */
    public function execute()
    {
        /* @var MultipleWishlistHelper $helper */
        $helper = $this->_objectManager->get(MultipleWishlistHelper::class);
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        if (!$helper->isMultipleEnabled()) {
            if ($helper->getWishlist() && $helper->getWishlist()->getItemCollection()->getSize()) {
                try {
                    foreach ($helper->getWishlist()->getItemCollection() as $item) {
                        $item->delete();
                    }
                    $resultJson->setData([
                        "status" => true
                    ]);
                } catch (Exception $e) {
                    $resultJson->setData([
                        "status" => false
                    ]);
                }
            }
        }
        return $resultJson;
    }
}

