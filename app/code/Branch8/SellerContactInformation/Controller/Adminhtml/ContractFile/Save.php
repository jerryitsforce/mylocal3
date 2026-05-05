<?php

namespace Branch8\SellerContactInformation\Controller\Adminhtml\ContractFile;

use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Backend\Model\Auth\Session;


class Save extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;

    /**
     * @var \Branch8\SellerContactInformation\Model\ContractFilesFactory
     */
    protected $contractFilesFactory;

    protected $timezone;

    protected $_fileUploaderFactory;

    protected $_mediaDirectory;

    protected $_conn;

    protected $authSession;

    protected $contractLogFactory;

    protected $publisher;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Branch8\SellerContactInformation\Model\ContractFilesFactory $contractFilesFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Branch8\SellerContactInformation\Model\ContractFilesFactory $contractFilesFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Branch8\SellerContactInformation\Model\ContractUploadFactory $fileUploaderFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        Session $authSession,
        \Branch8\SellerContactInformation\Model\ContractLogFactory $contractLogFactory,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->contractFilesFactory = $contractFilesFactory;
        $this->timezone = $timezone;
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_conn = $resourceConnection->getConnection();
        $this->authSession = $authSession;
        $this->contractLogFactory = $contractLogFactory;
        $this->publisher = $publisher;
    }
    
    /**
     * Delete Contract file
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        $information = $this->getRequest()->getParam('information');
        $contractId = $information['entity_id'];
        $sellerId = $information['seller_id'];

        $resultRedirect = $this->resultRedirectFactory->create();
        $redirectUrl = $resultRedirect->setPath('customer/index/edit', ['id' => $sellerId, 'seller_panel' => 1]);
        try {
            $validateResult = $this->validateData($information);
            if($validateResult['error']){
                $this->messageManager->addErrorMessage($validateResult['message']);
                return $resultRedirect->setRefererUrl();
            }

            // init model and delete
            $model = $this->contractFilesFactory->create();
            $model->load($contractId);
            $dataBefore = $model->getData();
            $data = [
                'seller_id' => $sellerId,
                'contracts_from' => $information['contracts_from'],
                'contracts_to' => $information['contracts_to'],
                'update_existed_product' => $information['update_existed_product'],
                'commission' => $information['commission'],
                'min_commission' => $information['min_commission'],
                // 'is_active' => $information['is_active']
            ];
            if ($model->getId()) {
                $data['entity_id'] = $model->getId();
                // $fileName = $model->getData('file_name');
                // $sellerId = $model->getData('seller_id');
                // $filePath = 'marketplace/contracts_files/' . $sellerId . '/' . $fileName;
                // $model->delete();
                // $mediaRootDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
                // if ($this->file->isExists($mediaRootDir . $filePath)) {
                //     $this->file->deleteFile($mediaRootDir . $filePath);
                // }
                // $this->messageManager->addSuccess(__('The "'.$fileName.'" has been deleted.'));
            }else{
                $data['updated_at'] = NULL;
            }
            $data['updated_at'] = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
            $data['applicable_period'] = $this->timezone->date(new \DateTime($information['contracts_from']))->format('Y-m-d H:i:s')
                .' - '.$this->timezone->date(new \DateTime($information['contracts_to']))->format('Y-m-d H:i:s');
            $user = $this->authSession->getUser();
            $username = $user->getUsername();
            $data['updated_by'] = $username;
            $file = $_FILES['information']['name']['file_name'];
            
            if($file != ''){
                /**
                 * Upload
                 */
                $uploader = $this->_fileUploaderFactory->create(
                    ['fileId' => 'information[file_name]']
                );
                $uploader->setAllowedExtensions(
                    ['docx', 'pdf']
                );
                $uploader->setAllowRenameFiles(true);

                $target = $this->_mediaDirectory->getAbsolutePath('marketplace/contracts_files/'.$sellerId);
                $result = $uploader->save($target);
                $data['file_name'] = $result['file'];
            }

            $model->setData($data)->save();

            $model2 = $this->contractFilesFactory->create();
            $modelAfter = $model2->load($model->getId());
            $dataAfter = $modelAfter->getData();
            $contractDataHistory = array_diff_assoc($dataAfter, $dataBefore);
            if($modelAfter->getIsActive() == 1 && (isset($contractDataHistory['commission']) || isset($contractDataHistory['min_commission']))){
                $sqlUpdateCommission = 'update marketplace_saleperpartner set commission_rate='.$modelAfter->getCommission().', min_commission_rate='.$modelAfter->getMinCommission().' 
                    where seller_id='.$modelAfter->getSellerId();
                $this->_conn->query($sqlUpdateCommission);
            }

            /**
             * Add queue to update product commission
             * if change an active rule + update_existed_product = 1 => add queue update product
             */
            if( $modelAfter->getIsActive() == 1 && 
                (
                    (isset($contractDataHistory['update_existed_product']) && (int)$contractDataHistory['update_existed_product'])
                    || ($modelAfter->getData('update_existed_product') && isset($contractDataHistory['commission']))
                )
            ){
                $queueData = [
                    'seller_id' => $sellerId,
                    'action' => \Branch8\SellerContactInformation\Model\Consumer::ACTION_ACTIVE_CONTRACT_CHANGE
                ];
                $this->publisher->publish(
                    'seller.contract.active.update_product',
                    json_encode($queueData)
                );
            }

            /**
             * Save History
             */
            
            $contractDataHistory['contract_id'] = $modelAfter->getId();
            $contractDataHistory['entity_id'] = NULL;
            $contractDataHistory['seller_id'] = $dataAfter['seller_id'];
            $this->contractLogFactory->create()->setData($contractDataHistory)->save();
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        
        return $redirectUrl;
    }

    protected function validateData($information){
        $result = ['error' => false];

        $contractId = $information['entity_id'];
        $sellerId = $information['seller_id'];

        $contractFrom = $information['contracts_from'];
        $contractTo = $information['contracts_to'];
        $currentTime8 = $this->timezone->date();
        $currentTime0 = $this->timezone->convertConfigTimeToUtc($currentTime8);
        
        if(strtotime($contractTo) < strtotime($currentTime0)){
            $result = ['error' => true, 'message' => __('End date must be in the future.')];
            return $result;
        }
        if(strtotime($contractTo) <= strtotime($contractFrom)){
            $result = ['error' => true, 'message' => __('End date must be greater than start date')];
            return $result;
        }
        if((int)$information['commission'] < (int)$information['min_commission']){
            $result = ['error' => true, 'message' => __('The commission rate cannot be less than the minimum commission rate.')];
            return $result;
        }
        /**
         * Validate overlap range
         */
        if($contractId){
            $sqlCheckOverlap = 'select count(*) as cnt from seller_contract_files where seller_id='.$sellerId.' and entity_id <> '.$contractId.' and 
            ((contracts_from < "'.$contractFrom.'" and contracts_to > "'.$contractFrom.'") || (contracts_from < "'.$contractTo.'" and contracts_to > "'.$contractTo.'"))';
        }else{
            $sqlCheckOverlap = 'select count(*) as cnt from seller_contract_files where seller_id='.$sellerId.' and 
            ((contracts_from < "'.$contractFrom.'" and contracts_to > "'.$contractFrom.'") || (contracts_from < "'.$contractTo.'" and contracts_to > "'.$contractTo.'"))';
        }
        
        $isOverlap = $this->_conn->fetchOne($sqlCheckOverlap);
        if($isOverlap){
            $result = ['error' => true, 'message' => __('Contract overlaps time period')];
            return $result;
        }
        $modelValidate = $this->contractFilesFactory->create();
        $modelValidate->load($contractId);
        if($modelValidate->getIsActive()){
            $currentTime = $this->timezone->date()->format('Y-m-d H:i:s');
            $toDate = $this->timezone->date(new \DateTime($information['contracts_to']))->format('Y-m-d H:i:s');
            if(strtotime($toDate) < strtotime($currentTime)){
                $result = ['error' => true, 'message' => __("The contract is active, the end date cannot be updated to be less than the current time.")];
                return $result;
            }
        }
        $fromDate = $this->timezone->date(new \DateTime($information['contracts_from']))->format('Y-m-d H:i:s');
        $toDate = $this->timezone->date(new \DateTime($information['contracts_to']))->format('Y-m-d H:i:s');
        if(strtotime($toDate) < strtotime($fromDate)){
            $result = ['error' => true, 'message' => __("The end date cannot be less than the start date.")];
            return $result;
        }

        if($information['commission'] < $information['min_commission']){
            $result = ['error' => true, 'message' => __("The commission rate cannot be less than the minimum commission.")];
            return $result;
        }

        return $result;
    }
        
}
