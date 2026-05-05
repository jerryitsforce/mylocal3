<?php

namespace Branch8\Customer\Controller\Adminhtml\Group;

use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\GroupFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;

class Save extends \Magento\Customer\Controller\Adminhtml\Group\Save{
    private $groupExtensionInterfaceFactory;
    /**
     * @var \Branch8\Customer\Helper\Group
     */
    protected $customerGroupHelper;
    /**
     * @var Filesystem
     */
    protected $filesystem;
    /**
     * @var UploaderFactory
     */
    protected $fileUploader;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param GroupRepositoryInterface $groupRepository
     * @param GroupInterfaceFactory $groupDataFactory
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param \Magento\Customer\Api\Data\GroupExtensionInterfaceFactory $groupExtensionInterfaceFactory
     * @param \Branch8\Customer\Helper\Group $customerGroupHelper
     * @param Filesystem $filesystem
     * @param UploaderFactory $fileUploader
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        GroupRepositoryInterface $groupRepository,
        GroupInterfaceFactory $groupDataFactory,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        \Magento\Customer\Api\Data\GroupExtensionInterfaceFactory $groupExtensionInterfaceFactory,
        \Branch8\Customer\Helper\Group $customerGroupHelper,
        Filesystem $filesystem,
        UploaderFactory $fileUploader
    ) {
        parent::__construct($context, $coreRegistry, $groupRepository, $groupDataFactory,
            $resultForwardFactory, $resultPageFactory, $dataObjectProcessor, $groupExtensionInterfaceFactory);
        $this->groupExtensionInterfaceFactory = $groupExtensionInterfaceFactory
            ?: ObjectManager::getInstance()->get(\Magento\Customer\Api\Data\GroupExtensionInterfaceFactory::class);
        $this->customerGroupHelper = $customerGroupHelper;

        $this->filesystem = $filesystem;
        $this->fileUploader = $fileUploader;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Forward|\Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $taxClass = (int)$this->getRequest()->getParam('tax_class');

        /** @var \Magento\Customer\Api\Data\GroupInterface $customerGroup */
        $customerGroup = null;
        if ($taxClass) {
            $id = $this->getRequest()->getParam('id');
            $websitesToExclude = empty($this->getRequest()->getParam('customer_group_excluded_websites'))
                ? [] : $this->getRequest()->getParam('customer_group_excluded_websites');
            $resultRedirect = $this->resultRedirectFactory->create();
            try {
                $customerGroupCode = (string)$this->getRequest()->getParam('code');

                if ($id !== null) {
                    $customerGroup = $this->groupRepository->getById((int)$id);
                    $customerGroupCode = $customerGroupCode ?: $customerGroup->getCode();
                } else {
                    $customerGroup = $this->groupDataFactory->create();
                }
                $customerGroup->setCode(!empty($customerGroupCode) ? $customerGroupCode : null);
                $customerGroup->setTaxClassId($taxClass);
                $customerGroupExtensionAttributes = $this->groupExtensionInterfaceFactory->create();
                //set new custom field
                $customerGroupExtensionAttributes = $this->customerGroupHelper->setExtAttributes($this->getRequest(), $customerGroupExtensionAttributes);
                if ($websitesToExclude !== null) {
                    $customerGroupExtensionAttributes->setExcludeWebsiteIds($websitesToExclude);
                }
                $customerGroup->setExtensionAttributes($customerGroupExtensionAttributes);
                /**
                 * Upload file icon
                 */
                if(!$this->uploadIconFile()){
                    throw new \Magento\Framework\Exception\LocalizedException(__('Can\'t upload icon file.'));
                }

                $this->groupRepository->save($customerGroup);

                $this->messageManager->addSuccessMessage(__('You saved the customer group.'));
                $resultRedirect->setPath('customer/group');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                if ($customerGroup != null) {
                    $this->storeCustomerGroupDataToSession(
                        $this->dataObjectProcessor->buildOutputDataArray(
                            $customerGroup,
                            \Magento\Customer\Api\Data\GroupInterface::class
                        )
                    );
                }
                $resultRedirect->setPath('customer/group/edit', ['id' => $id]);
            }
            return $resultRedirect;
        } else {
            return $this->resultForwardFactory->create()->forward('new');
        }
    }

    public function uploadIconFile()
    {
        // this folder will be created inside "pub/media" folder
        $iconFolder = \Branch8\Customer\Helper\Group::ICON_FOLDER;

        // "upload_custom_file" is the HTML input file name
        $iconInputFileName = 'icon';

        try{
            $file = $this->getRequest()->getFiles($iconInputFileName);
            $fileName = ($file && array_key_exists('name', $file)) ? $file['name'] : null;
            $mediaDirectory = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
            if ($file && $fileName) {
                $target = $mediaDirectory->getAbsolutePath($iconFolder);

                /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
                $uploader = $this->fileUploader->create(['fileId' => $iconInputFileName]);

                // set allowed file extensions
                $uploader->setAllowedExtensions(['jpg', 'pdf', 'doc', 'png']);

                // allow folder creation
                $uploader->setAllowCreateFolders(true);

                // rename file name if already exists
                $uploader->setAllowRenameFiles(false);

                // upload file in the specified folder
                $result = $uploader->save($target);

                //echo '<pre>'; print_r($result); exit;

                return true;
            }else{
                //no change image
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
}