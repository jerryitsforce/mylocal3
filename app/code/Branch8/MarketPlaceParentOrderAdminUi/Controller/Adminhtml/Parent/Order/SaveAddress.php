<?php

namespace Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\Parent\Order;

use Branch8\MarketPlaceParentOrder\Model\ParentOrderAddressFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;

/**
 * Edit CMS block action.
 */
class SaveAddress extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    private $_coreRegistry;
    private ParentOrderAddressFactory $parentOrderAddressFactory;
    /**
     * @var RegionFactory|mixed
     */
    private mixed $regionFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param ParentOrderAddressFactory $parentOrderAddressFactory
     * @param RegionFactory|null $regionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context        $context,
        \Magento\Framework\Registry                $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        ParentOrderAddressFactory                  $parentOrderAddressFactory,
        RegionFactory                              $regionFactory = null
    )
    {
        $this->regionFactory = $regionFactory ?: ObjectManager::getInstance()->get(RegionFactory::class);
        $this->resultPageFactory = $resultPageFactory;
        $this->parentOrderAddressFactory = $parentOrderAddressFactory;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Edit CMS block
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $addressId = $this->getRequest()->getParam('entity_id');
        /**
         * @var $address ParentOrderAddress
         */
        $address = $this->parentOrderAddressFactory->create()->load($addressId);
        $data = $this->getRequest()->getPostValue();
        $data = $this->updateRegionData($data);
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data && $address->getId()) {
            $address->addData($data);
            try {
                $address->save();
                $this->messageManager->addSuccessMessage(__('You updated the order address.'));
                return $resultRedirect->setPath('sales/*/address', ['address_id' => $address->getId()]);
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('We can\'t update the order address right now.'));
            }
            return $resultRedirect->setPath('sales/*/address', ['address_id' => $address->getId()]);
        } else {
            return $resultRedirect->setPath('sales/*/');
        }
    }

    /**
     * Update region data
     *
     * @param array $attributeValues
     * @return array
     */
    private function updateRegionData($attributeValues)
    {
        if (!empty($attributeValues['region_id'])) {
            $newRegion = $this->regionFactory->create()->load($attributeValues['region_id']);
            $attributeValues['region_code'] = $newRegion->getCode();
            $attributeValues['region'] = $newRegion->getDefaultName();
        }
        return $attributeValues;
    }
}
