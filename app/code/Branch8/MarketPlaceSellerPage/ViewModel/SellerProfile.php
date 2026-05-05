<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceSellerPage\ViewModel;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Webkul\Marketplace\Helper\Data as MpHelper;

/**
 *
 */
class SellerProfile implements ArgumentInterface
{
    /**
     * @var MpHelper
     */
    private $mpHelper;

    private Json $json;

    /**
     * @param MpHelper $mpHelper
     * @param Json $json
     */
    public function __construct(
        MpHelper $mpHelper,
        Json     $json
    )
    {
        $this->json = $json;
        $this->mpHelper = $mpHelper;
    }

    /**
     * @return bool|\Webkul\Marketplace\Model\Seller
     */
    public function getSellerProfile()
    {
        try {
            /**
             * @var $seller \Webkul\Marketplace\Model\Seller
             */
            $seller = $this->mpHelper->getProfileDetail(MpHelper::URL_TYPE_LOCATION);
            $noimage = 'noimage.png';
            $bannerPic = $seller->getBannerPic() ? $seller->getBannerPic() : $noimage;
            $shopName = $seller->getShopTitle() ? $seller->getShopTitle() : '';
            return $this->json->serialize(
                [
                    'sellerId' => $seller->getSellerId(),
                    'shopName' => $shopName ?: '',
                    'shopLogo' => $this->mpHelper->getMediaUrl() . 'avatar/' . $bannerPic
                ]
        );
        } catch (\Exception $exception) {
            return false;
        }
    }
}
