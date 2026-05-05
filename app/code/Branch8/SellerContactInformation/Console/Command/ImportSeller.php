<?php
namespace Branch8\SellerContactInformation\Console\Command;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Webkul\Marketplace\Model\SellerFactory;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollectionFactory;
use Webkul\Marketplace\Model\SaleperpartnerFactory;
use Webkul\Marketplace\Model\ResourceModel\Saleperpartner\CollectionFactory as SaleperpartnerCollectionFactory;

/**
 * Class ImportSeller
 * @package Branch8\SellerContactInformation\Console\Command
 */
class ImportSeller extends Command
{
    /**
     * @var State
     */
    protected $appState;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var Csv
     */
    protected $csv;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var StoreManagerInterface
    */
    protected $storeManager;

    /**
     * @var CustomerInterfaceFactory
    */
    protected $customerFactory;

    /**
     * @var CustomerRepositoryInterface
    */
    protected $customerRepository;

    /**
     * @var EncryptorInterface
    */
    protected $encryptor;

    /**
     * @var SellerFactory
    */
    protected $sellerFactory;

     /**
     * @var SellerCollectionFactory
    */
    protected $sellerCollectionFactory;

    /**
     * @var SaleperpartnerFactory
    */
    protected $saleperpartnerFactory;

     /**
     * @var SaleperpartnerCollectionFactory
    */
    protected $saleperpartnerCollectionFactory;
    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    public $logger;

    /**
     * @param State $appState
     * @param DirectoryList $directoryList
     * @param Csv $csv
     * @param File $file
     * @param StoreManagerInterface $storeManager
     * @param CustomerInterfaceFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param EncryptorInterface $encryptor
     * @param SellerFactory $sellerFactory
     * @param SellerCollectionFactory $sellerCollectionFactory
     * @param SaleperpartnerFactory $saleperpartnerFactory
     * @param SaleperpartnerCollectionFactory $saleperpartnerCollectionFactory
     * @param TimezoneInterface $timezoneInterface
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        State $appState,
        DirectoryList $directoryList,
        Csv $csv,
        File $file,
        StoreManagerInterface $storeManager,
        CustomerInterfaceFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        EncryptorInterface $encryptor,
        SellerFactory $sellerFactory,
        SellerCollectionFactory $sellerCollectionFactory,
        SaleperpartnerFactory $saleperpartnerFactory,
        SaleperpartnerCollectionFactory $saleperpartnerCollectionFactory,
        TimezoneInterface $timezoneInterface,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->appState = $appState;
        $this->directoryList = $directoryList;
        $this->csv = $csv;
        $this->file = $file;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->encryptor = $encryptor;
        $this->sellerFactory = $sellerFactory;
        $this->sellerCollectionFactory = $sellerCollectionFactory;
        $this->saleperpartnerFactory = $saleperpartnerFactory;
        $this->saleperpartnerCollectionFactory = $saleperpartnerCollectionFactory;
        parent::__construct();
        $this->timezone = $timezoneInterface;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('hotai:import_seller');
        $this->setDescription('Import or update seller info');
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/command_import_update_seller.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Start import seller information');

        $csvFilePath = 'pub/media/marketplace/seller.csv';
        $rootDirectory = $this->directoryList->getRoot();
        $csvFile = $rootDirectory . "/" . $csvFilePath;

        $adminRoleFilePath = 'pub/media/marketplace/rolesID.csv';
        $adminRoleFile = $rootDirectory . "/" . $adminRoleFilePath;

        try {
            if ($this->file->isExists($csvFile)) {
                // Get Website ID
                $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();

                // Read roles data
                $this->csv->setDelimiter(",");
                $rolesData = $this->csv->getData($adminRoleFile);
                $rolesHeader = $rolesData[0];
                $allRow = [];
                if (!empty($rolesData)) {
                    foreach (array_slice($rolesData, 1) as $key => $row) {
                        $allRow[] = array_combine($rolesHeader, $row);
                    }
                }
                $arrayRolesId = [];
                foreach ($allRow as $key => $row) {
                    $arrayRolesId[$row['HotaiRoleId']] = $row['MagentoRoleId'];
                }

                $this->csv->setDelimiter(",");
                $data = $this->csv->getData($csvFile);
                $header = $data[0];
                $allRow = [];
                if (!empty($data)) {
                    foreach (array_slice($data, 1) as $key => $row) {
                        $allRow[] = array_combine($header, $row);
                    }
                }
                foreach ($allRow as $key => $row) {
                    $autoId = '';
                    $customerId = '';
                    $skipRow = false;
                    //find seller by seller_code, if not found => find/create customer
                    $collection = $this->sellerCollectionFactory->create()
                        ->addFieldToFilter('seller_code', $row['No'])
                        ->addFieldToFilter('store_id', 0);
                    if (count($collection)) {
                        foreach ($collection as $sellerData) {
                            $autoId = $sellerData->getId();
                            $customerId = $sellerData->getData('seller_id');
                        }
                    }

                    $uniqueEmail = $row['No'] . '@hotaiconnected.com.tw';
                    if(empty($autoId)) {
                        //find customer by email
                        try {
                            $existCustomer = $this->customerRepository->get($uniqueEmail,$websiteId);
                            $customerId = $existCustomer->getId();
                        } catch (\Exception $e) {
                            $this->logger->critical($e);
                        }

                        //create customer
                        if(!$customerId) {
                            $customer = $this->customerFactory->create();
                            $customer->setWebsiteId($websiteId);
                            $customer->setEmail($uniqueEmail);
                            $customer->setFirstname('Seller');
                            $customer->setGroupId(18);//seller group
                            $customer->setLastname($row['No']);
                            $customer->setCustomAttribute('platform', 'seller');
                            $hashedPassword = $this->encryptor->getHash('Seller@123', true);
                            try {
                                $newCustomer = $this->customerRepository->save($customer, $hashedPassword);
                                $customerId = $newCustomer->getId();
                                $logger->info('Success create customer (seller) ' . $uniqueEmail);
                            } catch (\Exception $e) {
                                $logger->info('Error when create customer');
                                $logger->info($e->getMessage());
                                $skipRow = true;
                            }
                        } else {
                            //find seller by seller_id, add this code to fix bug lost data on field seller_code
                            $collection = $this->sellerCollectionFactory->create()
                                ->addFieldToFilter('seller_id', $customerId)
                                ->addFieldToFilter('store_id', 0);
                            if (count($collection)) {
                                foreach ($collection as $sellerData) {
                                    $autoId = $sellerData->getId();
                                }
                            }
                        }
                    }
                    if ($skipRow) {
                        continue;
                    }

                    /* hotai new site don't have these field
                        MainContactPersonSex
                        MainContactPersonCompanyPhone
                        MainContactPersonCompanyPhoneExt
                        AccountingContactPersonSex
                        AccountingContactPerson2
                        AccountingContactPerson2Sex
                        AccountingContactPerson2Email
                        InvoiceZipcode
                        InvoiceEmail
                        LogisticsAddress
                        PlatformCharge
                        SaleIncentive
                        TotalCommission
                        IsLogisticsFeeSubsidized
                        LogisticsFeeSubsidizedThreshold
                        ShippingRiderEndDate
                        ShippingRiderStartDate
                        IsShippingPeriod
                        HasGoodsPickupLocation
                        IsGoodsPickupLocation
                    */
                    try {
                        $sellerId = $customerId;
                        $seller = $this->sellerFactory->create();
                        $action = 'create';
                        if ($autoId) {
                            $seller->load($autoId);
                            $action = 'update';
                        }

                        $seller->setData('seller_id', $sellerId);
                        $seller->setData('seller_code', $row['No']);//new field replace register_account
                        $seller->setData('company_name', $row['InvoiceCompanyName']);
                        $seller->setData('company_address', $row['InvoiceAddress']);
                        $seller->setData('company_phone', $row['MainContactPersonPhone']);
                        $seller->setData('company_representative', $row['MainContactPersonCompanyPhone']);
                        $seller->setData('is_seller', $row['IsEnabled']);
                        $seller->setData('shop_title', $row['Name']);

                        $seller->setData('primary_contact', $row['MainContactPerson']);
                        $seller->setData('primary_contact_email', $row['MainContactPersonEmail']);
                        $seller->setData('contact_number', $row['MainContactPersonPhone']);
                        $seller->setData('financial_liaison', $row['AccountingContactPerson']);
                        $seller->setData('financial_liaison_email', $row['AccountingContactPersonEmail']);

                        $seller->setData('invoice_company_no', $row['InvoiceCompanyNo']);
                        $seller->setData('invoice_company_name', $row['InvoiceCompanyName']);
                        $seller->setData('invoice_company_address', $row['InvoiceAddress']);
                        $seller->setData('bank', $row['BankAccountName']);
                        $seller->setData('bank_type', $row['BankType']);
                        $seller->setData('bank_branch_type', $row['BankBranchType']);
                        $seller->setData('bank_code', $row['BankNo']);
                        $seller->setData('bank_branch_no', $row['BankBranchNo']);
                        $seller->setData('bank_account', $row['BankAccount']);
                        $seller->setData('bank_memo', $row['BankMemo']);

                        $seller->setData('logistics_and_delivery_contract', $row['LogisticsContactPerson']);
                        $seller->setData('logistics_and_delivery_phone_number', $row['LogisticsPhone']);
                        $seller->setData('logistics_and_delivery_email', $row['LogisticsEmail']);

                        $seller->setData('shipping_methods', 'hotai_711,hotai_delivery');
                        $seller->setData('preservation_status', ',refrigerated,frozen');

                        $roleId = isset($arrayRolesId[$row['BusinessResponsibleManagerId']]) ? (int) $arrayRolesId[$row['BusinessResponsibleManagerId']] : 1;
                        $seller->setData('salesperson', $roleId);
                        if ($row['CreateDateTime'] == 'NULL') {
                            $row['CreateDateTime'] = '2022-01-01';
                        }
                        if ($row['UpDateTime'] == 'NULL') {
                            $row['UpDateTime'] = '2022-01-01';
                        }
                        $createdTime = $this->timezone->convertConfigTimeToUtc($row['CreateDateTime'], 'Y-m-d H:i:s');
                        $updatedTime = $this->timezone->convertConfigTimeToUtc($row['UpDateTime'], 'Y-m-d H:i:s');
                        $seller->setData('created_at', $createdTime);
                        $seller->setData('updated_at', $updatedTime);
                        $seller->save();
                        $logger->info('Success ' . $action . ' seller ' . $sellerId . ' (' . $row['No'] . ')');

                        //update Commission rate
                        $saleperpartnerCollection = $this->saleperpartnerCollectionFactory->create();
                        $saleperpartnerCollection->addFieldToFilter('seller_id', $sellerId);
                        if($saleperpartnerCollection->getSize()) {
                            $saleperpartnerAction = 'update';
                            $saleperpartner = $saleperpartnerCollection->getFirstItem();
                            $saleperpartner->setData('commission_rate', $row['TransCommission']);
                            $saleperpartner->setData('commission_status', 1);
                            $saleperpartner->save();
                        } else {
                            $saleperpartnerAction = 'create';
                            $saleperpartner = $this->saleperpartnerFactory->create();
                            $saleperpartner->setData('seller_id', $sellerId);
                            $saleperpartner->setData('commission_rate', $row['TransCommission']);
                            $saleperpartner->setData('commission_status', 1);
                            $saleperpartner->save();
                        }
                        $logger->info('Success ' . $saleperpartnerAction . ' commission rate for seller ' . $sellerId . ' (' . $row['No'] . ')');
                    } catch (\Exception $e) {
                        $logger->info('Error when ' . $action . ' seller ' . $sellerId . ' (' . $row['No'] . ')');
                        $logger->info($e->getMessage());
                    }
                }
            } else {
                $logger->info('Csv file not exist');
            }
        } catch (\Exception $e) {
            $logger->info('Error when import/update seller information');
            $logger->info($e->getMessage());
        }
        return 1;
    }
}
