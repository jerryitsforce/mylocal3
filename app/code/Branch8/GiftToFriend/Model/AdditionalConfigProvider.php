<?php
namespace Branch8\GiftToFriend\Model;

use Magento\Customer\Model\Session;

class AdditionalConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var \Branch8\GiftToFriend\Helper\Data
     */
    protected $b8GiftHelper;

    /**
     * @var \Branch8\GiftToFriend\Helper\GiftBox
     */
    protected $b8GiftBoxHelper;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepository;

    /**
     * AdditionalConfigProvider constructor.
     *
     * @param Session $session
     * @param \Branch8\GiftToFriend\Helper\Data $b8GiftHelper
     * @param \Branch8\GiftToFriend\Helper\GiftBox $b8GiftBoxHelper
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     */
    public function __construct(
        Session $session,
        \Branch8\GiftToFriend\Helper\Data $b8GiftHelper,
        \Branch8\GiftToFriend\Helper\GiftBox $b8GiftBoxHelper,
        \Magento\Framework\View\Asset\Repository $assetRepository
    ) {
        $this->session = $session;
        $this->b8GiftHelper = $b8GiftHelper;
        $this->b8GiftBoxHelper = $b8GiftBoxHelper;
        $this->assetRepository = $assetRepository;
    }

   public function getConfig()
   {
        $additionalVariables = [];
        $giftStep1 = $this->assetRepository->getUrl('Branch8_GiftToFriend::images/gift_step1.svg');
        $giftStep2 = $this->assetRepository->getUrl('Branch8_GiftToFriend::images/gift_step2.svg');
        $giftStep3 = $this->assetRepository->getUrl('Branch8_GiftToFriend::images/gift_step3.svg');
    
        if ($this->session->isLoggedIn()) {
            $additionalVariables['giftToFriend'] = [
                'is_active' => $this->b8GiftHelper->isFeatureEnable(),
                'placeholderAddress' => $this->b8GiftHelper->getPlaceholderAddress(),
                'salesPresentativeAddress' => $this->b8GiftBoxHelper->getSalesPresentativeAddr(),
                'giftStep1Url' => $giftStep1,
                'giftStep2Url' => $giftStep2,
                'giftStep3Url' => $giftStep3,
            ];
        }
            
        return $additionalVariables;
   }
}
