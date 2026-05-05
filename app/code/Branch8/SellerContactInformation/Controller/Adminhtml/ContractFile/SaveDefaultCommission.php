<?php

namespace Branch8\SellerContactInformation\Controller\Adminhtml\ContractFile;

use Magento\Backend\Model\Auth\Session;
use Branch8\SellerContactInformation\Model\Config\Source\ContractStatus;

class SaveDefaultCommission extends \Magento\Backend\App\Action
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
    protected $contractFilesCollectionFactory;

    protected $timezone;

    protected $_fileUploaderFactory;

    protected $_mediaDirectory;

    protected $_conn;

    protected $authSession;

    protected $contractLogFactory;

    protected $publisher;

    protected $partnerCollectionFactory;

    protected $resultJsonFactory;
    

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Branch8\SellerContactInformation\Model\ResourceModel\ContractFiles\CollectionFactory $contractFilesCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Webkul\Marketplace\Model\ResourceModel\Saleperpartner\CollectionFactory $partnerCollectionFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->timezone = $timezone;
        $this->_conn = $resourceConnection->getConnection();
        $this->publisher = $publisher;
        $this->partnerCollectionFactory = $partnerCollectionFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->contractFilesCollectionFactory = $contractFilesCollectionFactory;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $commissionRate = $this->getRequest()->getParam('default_commission_rate');
        $minCommissionRate = $this->getRequest()->getParam('default_min_commission_rate');
        $specialCommissionRate = $this->getRequest()->getParam('special_commission');
        $sellerId = $this->getRequest()->getParam('seller_id');
        
        $sellerPartner = $this->partnerCollectionFactory->create()
            ->addFieldToFilter('seller_id', $sellerId)
            ->getFirstItem();
        if(!$sellerPartner->getId()){
            try{
                $sellerPartner->setSellerId($sellerId)
                    ->setCommissionRate($commissionRate)
                    ->setMinCommissionRate($minCommissionRate)
                    ->setSpecialCommissionRate($specialCommissionRate)
                    ->setCommissionStatus(1)
                    ->setDefaultCommissionRate($commissionRate)
                    ->setDefaultMinCommissionRate($minCommissionRate)
                    ->setCreatedAt($this->timezone->date()->format('-Y-m-d H:i:s'))
                    ->save();
                $queueData = [
                    'seller_id' => $sellerId,
                    'action' => \Branch8\SellerContactInformation\Model\Consumer::ACTION_DEFAULT_SETTING_CHANGED
                ];
                $this->publisher->publish(
                    'seller.contract.active.update_product',
                    json_encode($queueData)
                );
                $resultJson->setData(['success' => 1]);
            }catch(\Exception $e){
                $resultJson->setData(['success' => 0]);
            }
            return $resultJson;
        }
        /** Now all partner must have commission_status = 1 */
        if($sellerPartner->getCommissionStatus() == 0){
            try{
                $sellerPartner->setSellerId($sellerId)
                    ->setCommissionRate($commissionRate)
                    ->setMinCommissionRate($minCommissionRate)
                    ->setSpecialCommissionRate($specialCommissionRate)
                    ->setCommissionStatus(1)
                    ->setDefaultCommissionRate($commissionRate)
                    ->setDefaultMinCommissionRate($minCommissionRate)
                    ->setCreatedAt($this->timezone->date()->format('-Y-m-d H:i:s'))
                    ->save();

                $queueData = [
                    'seller_id' => $sellerId,
                    'action' => \Branch8\SellerContactInformation\Model\Consumer::ACTION_DEFAULT_SETTING_CHANGED
                ];
                $this->publisher->publish(
                    'seller.contract.active.update_product',
                    json_encode($queueData)
                );
                $resultJson->setData(['success' => 1]);
            }catch(\Exception $e){
                $resultJson->setData(['success' => 0]);
            }
            return $resultJson;

        }

        try{
            $sellerPartner->setSellerId($sellerId)
                ->setDefaultCommissionRate($commissionRate)
                ->setCommissionStatus(1)
                ->setDefaultMinCommissionRate($minCommissionRate)
                ->setSpecialCommissionRate($specialCommissionRate)
                ->save();
            /** If no active contract, update commission rate and min commission rate */
            $contractFilesCnt = $this->contractFilesCollectionFactory->create()
                ->addFieldToFilter('seller_id', $sellerId)
                ->addFieldToFilter('is_active', ContractStatus::STATUS_ACTIVE)
                ->getSize();
            if(!$contractFilesCnt){
                $sellerPartner->setCommissionRate($commissionRate)
                    ->setMinCommissionRate($minCommissionRate)
                    ->save();
                $queueData = [
                    'seller_id' => $sellerId,
                    'action' => \Branch8\SellerContactInformation\Model\Consumer::ACTION_DEFAULT_SETTING_CHANGED
                ];
                $this->publisher->publish(
                    'seller.contract.active.update_product',
                    json_encode($queueData)
                );
            }
            $resultJson->setData(['success' => 1]);
        }catch(\Exception $e){
            $resultJson->setData(['success' => 0]);
        }
        return $resultJson;


        return $this->_redirect->redirect('');
    }

    
        
}
