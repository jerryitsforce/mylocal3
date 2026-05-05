<?php
namespace Branch8\Wishlist\Block\Customer;

class Wishlist extends \Magento\Wishlist\Block\Customer\Wishlist
{
    /**
     * Preparing global layout
     *
     * @return \Magento\Wishlist\Block\Customer\Wishlist
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('My Wish List'));
        $page = ($this->getRequest()->getParam('p'))? $this->getRequest()->getParam('p') : 1;
        $collection = $this->getWishlistItems()->setPageSize(12)->setCurPage((int)$page);
        if (!$this->getRequest()->isAjax()) {
            $this->getChildBlock('wishlist_item_pager')
                ->setAvailableLimit(array(12=>12,24=>24,36=>36))
                ->setIsShowPerPage(true)
                ->setFrameLength(
                    $this->_scopeConfig->getValue(
                        'design/pagination/pagination_frame',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    )
                )
                ->setCollection($collection);
        }
        $this->getWishlistItems()->load();
        return $this;
    }
}
