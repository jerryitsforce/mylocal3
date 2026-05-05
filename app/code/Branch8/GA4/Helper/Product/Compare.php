<?php
namespace Branch8\GA4\Helper\Product;

use Magento\Framework\DataObject;

class Compare extends \Magento\Catalog\Helper\Product\Compare
{
    public function getPostDataParams($product)
    {
        $params = ['product' => $product->getId()];
        if ($product->getData('ga4_settings')) {
            /** @var DataObject $ga4Settings */
            $ga4Settings = $product->getData('ga4_settings');
            if($ga4Settings->getData('item_list_id')) {
                $params['item_list_id'] = $ga4Settings->getData('item_list_id');
            }
            if($ga4Settings->getData('item_list_name')) {
                $params['item_list_name'] = $ga4Settings->getData('item_list_name');
            }

            if($ga4Settings->getData('promotion_id')) {
                $params['promotion_id'] = $ga4Settings->getData('promotion_id');
            }
            if($ga4Settings->getData('promotion_name')) {
                $params['promotion_id'] = $ga4Settings->getData('promotion_name');
            }
        }

        $requestingPageUrl = $this->_getRequest()->getParam('requesting_page_url');

        if (!empty($requestingPageUrl)) {
            $encodedUrl = $this->urlEncoder->encode($requestingPageUrl);
            $params[\Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED] = $encodedUrl;
        }

        return $this->postHelper->getPostData($this->getAddUrl(), $params);
    }
}
