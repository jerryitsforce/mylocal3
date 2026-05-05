<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\ViewModel;

use Branch8\WebkulMpBuyerSellerChat\Model\GeneralConfig;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 *
 */
class CoreConfig implements ArgumentInterface
{
    private UrlInterface $urlBuilder;
    private \Magento\Customer\Model\Session $session;
    private GeneralConfig $generalConfig;

    /**
     * @param \Magento\Customer\Model\Session $session
     * @param UrlInterface $urlBuilder
     * @param GeneralConfig $generalConfig
     */
    public function __construct(
        \Magento\Customer\Model\Session $session,
        UrlInterface                    $urlBuilder,
        GeneralConfig                   $generalConfig
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->session = $session;
        $this->generalConfig = $generalConfig;
    }

    /**
     * @return void
     */
    public function getGeneralConfig()
    {
        $this->generalConfig->getGeneralConfig();
    }

    /**
     * @return array
     */
    public function getChangProfileUploadConfig()
    {
        return [
            'url' => $this->urlBuilder->getUrl('mpchatsystem/chat/ChangeProfileImage'),
            'useProfileUrl' => $this->urlBuilder->getUrl('mpchatsystem/chat/UseProfileImage')
        ];
    }
}
