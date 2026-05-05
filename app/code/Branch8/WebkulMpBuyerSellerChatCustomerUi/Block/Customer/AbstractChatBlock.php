<?php
/**
 * Minichat Block Config
 */

namespace Branch8\WebkulMpBuyerSellerChatCustomerUi\Block\Customer;

use Branch8\WebkulMpBuyerSellerChat\Model\GeneralConfig;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\ArrayManager;
use Webkul\MpBuyerSellerChat\Helper\Http as HttpDriver;
use Webkul\MpBuyerSellerChat\Model\ResourceModel\CustomerBlock\CollectionFactory as CustomerBlockCollection;
use Webkul\MpBuyerSellerChat\Model\ResourceModel\Message\CollectionFactory;

class AbstractChatBlock extends \Webkul\MpBuyerSellerChat\Block\ChatHistory\Index
{
    /**
     * @var ArrayManager
     */
    protected $arrayManager;
    /**
     * @var GeneralConfig
     */
    protected $generalConfig;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Webkul\MpBuyerSellerChat\Model\EnableUserConfigProvider $configProvider
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Webkul\Marketplace\Helper\Data $mpHepler
     * @param Json $serializerJson
     * @param \Webkul\MpBuyerSellerChat\Api\CustomerDataRepositoryInterface $chatCustomerRepository
     * @param \Magento\Customer\Model\SessionFactory $customerSessionFactory
     * @param \Webkul\MpBuyerSellerChat\Model\ResourceModel\CustomerData\CollectionFactory $customerDataFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerModelFactory
     * @param CollectionFactory $dataCollection
     * @param \Magento\Framework\View\Asset\Repository $viewFileSystem
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Webkul\MpBuyerSellerChat\Helper\Data $buyerSellerHelper
     * @param CustomerBlockCollection $blockCustomerCollection
     * @param DriverFile $driverFile
     * @param HttpDriver $httpDrive
     * @param ArrayManager $arrayManager
     * @param GeneralConfig $generalConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context                             $context,
        \Webkul\MpBuyerSellerChat\Model\EnableUserConfigProvider                     $configProvider,
        \Magento\Framework\App\Request\Http                                          $request,
        \Webkul\Marketplace\Helper\Data                                              $mpHepler,
        \Magento\Framework\Serialize\Serializer\Json                                 $serializerJson,
        \Webkul\MpBuyerSellerChat\Api\CustomerDataRepositoryInterface                $chatCustomerRepository,
        \Magento\Customer\Model\SessionFactory                                       $customerSessionFactory,
        \Webkul\MpBuyerSellerChat\Model\ResourceModel\CustomerData\CollectionFactory $customerDataFactory,
        \Magento\Customer\Model\CustomerFactory                                      $customerModelFactory,
        CollectionFactory                                                            $dataCollection,
        \Magento\Framework\View\Asset\Repository                                     $viewFileSystem,
        \Magento\Store\Model\StoreManagerInterface                                   $storeManager,
        \Webkul\MpBuyerSellerChat\Helper\Data                                        $buyerSellerHelper,
        CustomerBlockCollection                                                      $blockCustomerCollection,
        DriverFile                                                                   $driverFile,
        HttpDriver                                                                   $httpDrive,
        ArrayManager                                                                 $arrayManager,
        GeneralConfig                                                                $generalConfig,
        array                                                                        $data = []
    )
    {
        parent::__construct(
            $context,
            $configProvider,
            $request,
            $mpHepler,
            $serializerJson,
            $chatCustomerRepository,
            $customerSessionFactory,
            $customerDataFactory,
            $customerModelFactory,
            $dataCollection,
            $viewFileSystem,
            $storeManager,
            $buyerSellerHelper,
            $blockCustomerCollection,
            $driverFile,
            $httpDrive,
            $data
        );
        $this->arrayManager = $arrayManager;
        $this->generalConfig = $generalConfig;
    }

    /**
     * @return string
     */


    /**
     * @return array
     */
    protected function getConfig()
    {
        $mediaUrl = $this->getUrl('mpchatsystem/index/viewfile?image=');
        $mediaUrl = str_replace("image=/", "image=", $mediaUrl);
        return [
            'miniChatConfig' => [
                'chatIcon' => $this->getViewFileUrl('Branch8_WebkulMpBuyerSellerChatCustomerUi::/image/chat-conversation.svg'),
                'chatText' => $this->getViewFileUrl('Branch8_WebkulMpBuyerSellerChatCustomerUi::/image/chat-text.svg'),
                'downIcon' => $this->getViewFileUrl('Branch8_WebkulMpBuyerSellerChatCustomerUi::/image/down-arrow.svg'),
                'mediaUrl' => $mediaUrl
            ]
        ];
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        if(!$customerId = $this->customerSessionFactory->create()->getCustomerId()){
            return '';
        }

        if (!$this->generalConfig->enableChat()) {
            return '';
        }
        return parent::toHtml();
    }
}
