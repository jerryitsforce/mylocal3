<?php
/**
 * Copyright © Magento,
Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\Customer\Override\Controller\Address;

use Branch8\OneStepCheckout\Model\Source\HotaiAddressType;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressExtensionFactory;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data as HelperData;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Filesystem;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\View\Result\PageFactory;

/**
 * Customer Address Form Post Controller
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FormPost extends \Magento\Customer\Controller\Address\FormPost
{
    protected $_filesystem;
    private AddressExtensionFactory $addressExtensionFactory;

    public function __construct(
            Context $context,
            Session $customerSession,
            FormKeyValidator $formKeyValidator,
            FormFactory $formFactory,
            AddressRepositoryInterface $addressRepository,
            AddressInterfaceFactory $addressDataFactory,
            RegionInterfaceFactory $regionDataFactory,
            DataObjectProcessor $dataProcessor,
            DataObjectHelper $dataObjectHelper,
            ForwardFactory $resultForwardFactory,
            PageFactory $resultPageFactory,
            RegionFactory $regionFactory,
            HelperData $helperData,
            AddressExtensionFactory $addressExtensionFactory,
            Filesystem $filesystem = null
        ) {
            $this->addressExtensionFactory = $addressExtensionFactory;
            parent::__construct(
                $context,
                $customerSession,
                $formKeyValidator,
                $formFactory,
                $addressRepository,
                $addressDataFactory,
                $regionDataFactory,
                $dataProcessor,
                $dataObjectHelper,
                $resultForwardFactory,
                $resultPageFactory,
                $regionFactory,
                $helperData,
                $filesystem
            );
        }

    /**
     * Process address form save
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $hotaiAddressType = $this->getRequest()->getPost('hotai_address_type');
        $tabHref = $hotaiAddressType == HotaiAddressType::ADDRESS_TYPE_NORMAL ? '' : '?tab=convenience_store';
        $redirectUrl = null;
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        if (!$this->getRequest()->isPost()) {
            $this->_getSession()->setAddressFormData($this->getRequest()->getPostValue());
            return $this->resultRedirectFactory->create()->setUrl(
                $this->_redirect->error($this->_buildUrl('*/*/edit', ['type' => $hotaiAddressType]))
            );
        }

        try {
            $address = $this->_extractAddress();
            if ($this->_request->getParam('delete_attribute_value')) {
                $address = $this->deleteAddressFileAttribute($address);
            }
            $this->_addressRepository->save($address);
            $this->messageManager->addSuccessMessage(__('You saved the address.'));
            $url = $this->_buildUrl('*/*/index/' , ['_secure' => true]) . $tabHref;
            return $this->resultRedirectFactory->create()->setUrl($this->_redirect->success($url));
        } catch (InputException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            foreach ($e->getErrors() as $error) {
                $this->messageManager->addErrorMessage($error->getMessage());
            }
        } catch (\Exception $e) {
            $redirectUrl = $this->_buildUrl('*/*/index/')  . $tabHref;
            $this->messageManager->addExceptionMessage($e, __('We can\'t save the address.'));
        }

        $url = $redirectUrl;
        if (!$redirectUrl) {
            $this->_getSession()->setAddressFormData($this->getRequest()->getPostValue());
            $url = $this->_buildUrl('*/*/edit', ['id' => $this->getRequest()->getParam('id'), 'type' => $hotaiAddressType]);
        }

        return $this->resultRedirectFactory->create()->setUrl($this->_redirect->error($url));
    }

    /**
     * Extract address from request
     *
     * @return \Magento\Customer\Api\Data\AddressInterface
     */
    protected function _extractAddress()
    {
        $existingAddressData = $this->getExistingAddressData();
        $addressType = $this->getRequest()->getParam('hotai_address_type');

        /** @var \Magento\Customer\Model\Metadata\Form $addressForm */
        $addressForm = $this->_formFactory->create(
            'customer_address',
            'customer_address_edit',
            $existingAddressData
        );
        $addressData = $addressForm->extractData($this->getRequest());
        $attributeValues = $addressForm->compactData($addressData);

        $this->updateRegionData($attributeValues);

        /** @var \Magento\Customer\Api\Data\AddressInterface $addressDataObject */
        $addressDataObject = $this->addressDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $addressDataObject,
            array_merge($existingAddressData, $attributeValues),
            \Magento\Customer\Api\Data\AddressInterface::class
        );
        $addressDataObject->setCustomerId($this->_getSession()->getCustomerId())
            ->setIsDefaultBilling(
                $this->getRequest()->getParam(
                    'default_billing',
                    isset($existingAddressData['default_billing']) ? $existingAddressData['default_billing'] : false
                )
            )
            ->setIsDefaultShipping(
                $addressType === HotaiAddressType::ADDRESS_TYPE_NORMAL
                    ? $this->getRequest()->getParam('default_shipping', false)
                    : false
            );


        $extensionAttributes = $addressDataObject->getExtensionAttributes();
        $extensionAttributes = $extensionAttributes ? : $this->addressExtensionFactory->create();

        $extensionAttributes->setIsDefaultConvenienceStore(
            $addressType === HotaiAddressType::ADDRESS_TYPE_CONVENIENCE_STORE
            ? $this->getRequest()->getParam('default_convenience_store', false)
            : false
        );

        $addressDataObject->setExtensionAttributes($extensionAttributes);

        return $addressDataObject;
    }


    /**
     * Removes file attribute from customer address and file from filesystem
     *
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return mixed
     */
    private function deleteAddressFileAttribute($address)
    {
        $attributeValue = $address->getCustomAttribute($this->_request->getParam('delete_attribute_value'));
        if ($attributeValue!== null) {
            if ($attributeValue->getValue() !== '') {
                $mediaDirectory = $this->getFileSystem()->getDirectoryWrite(DirectoryList::MEDIA);
                $fileName = $attributeValue->getValue();
                $path = $mediaDirectory->getAbsolutePath('customer_address' . $fileName);
                if ($fileName && $mediaDirectory->isFile($path)) {
                    $mediaDirectory->delete($path);
                }
                $address->setCustomAttribute(
                    $this->_request->getParam('delete_attribute_value'),
                    ''
                );
            }
        }

        return $address;
    }

    public function getFileSystem()
    {
        if ($this->_filesystem === null) {
            $this->_filesystem = ObjectManager::getInstance()->get(Filesystem::class);
        }
        return $this->_filesystem ;
    }
}
