<?php

namespace Branch8\MarketplaceSubAccount\Console;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollectionFactory;
class ImportSubAccount extends Command
{
    /**
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * @var \Branch8\MarketplaceSubAccount\Helper\Import
     */
    protected $importSubAccountHelper;
    /**
     * @var SellerCollectionFactory
     */
    protected $sellerCollection;
    /**
     * @var \Webkul\SellerSubAccount\Model\SubAccount\Source\Permissionsacc
     */
    protected $permissionsacc;
    /**
     * @var State
     */
    protected $state;

    protected $resourceConnection;

    protected $customerRepository;

    /**
     * @param DirectoryList $directoryList
     * @param \Branch8\MarketplaceSubAccount\Helper\Import $importSubAccountHelper
     * @param SellerCollectionFactory $sellerCollectionFactory
     * @param \Webkul\SellerSubAccount\Model\SubAccount\Source\Permissionsacc $permissionsacc
     * @param State $state
     */
    public function __construct(
        DirectoryList $directoryList,
        \Branch8\MarketplaceSubAccount\Helper\Import $importSubAccountHelper,
        SellerCollectionFactory $sellerCollectionFactory,
        \Webkul\SellerSubAccount\Model\SubAccount\Source\Permissionsacc $permissionsacc,
        State $state,
        ResourceConnection $resourceConnection,
        CustomerRepositoryInterface $customerRepository
    ){
        parent::__construct();
        $this->directoryList = $directoryList;
        $this->importSubAccountHelper = $importSubAccountHelper;
        $this->sellerCollection = $sellerCollectionFactory;
        $this->permissionsacc = $permissionsacc;
        $this->state = $state;
        $this->resourceConnection = $resourceConnection;
        $this->customerRepository = $customerRepository;
    }
    protected function configure()
    {
        $this->setName('sub_account:import');
        $this->setDescription('Import sub account');

        parent::configure();
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);
        $conn = $this->resourceConnection->getConnection();
        $subAccountFile = 'marketplace/sub_account.csv';
        $subAccountPath = $this->directoryList->getPath('media').'/'.$subAccountFile;
        if(!file_exists($subAccountPath)){
            $output->writeln('Import file not found.');
        }

        $handle = fopen($subAccountPath, 'r');
        $errorAccount = [];
        $permissionOptions = $this->permissionsacc->toOptionArray();
        $allPermissions = [];
        foreach($permissionOptions as $_permission){
            $allPermissions[] = $_permission['value'];
        }
        /**
         * $row[
         *      1 => email
         *      2 => name
         *      3 => phone
         *      4 => code
         * ]
         */
        $cnt = 0;
        while(! feof($handle)){
            $row = fgetcsv($handle);
            $cnt ++;
            if($cnt == 1 || $row == false){
                continue;
            }
            echo $cnt."\n";
            foreach($row as &$_col){
                $_col = trim($_col);
            }
            $sellerCode = $row[4];
            $sellerData = $this->sellerCollection->create()
                ->addFieldToSelect('seller_id')
                ->addFieldToFilter('seller_code', $sellerCode)
                ->getFirstItem();
            if(!$sellerData->getSellerId()){
                $errorAccount[$row[1]] = 'Could not found seller';
                continue;
            }
            //validate customer
            //for now only one website
            $sqlCustomer = 'select entity_id from customer_entity where email="'.$row[1].'"';
            $isExistedCustomer = $conn->fetchOne($sqlCustomer);
            if($isExistedCustomer){
                $this->customerRepository->deleteById($isExistedCustomer);
                usleep(10000);
            }
            $sellerId = $sellerData->getSellerId();
            $dataForImport = [
                'seller' => [
                    'seller_id' => $sellerId
                ],
                'sub_account' => [
                    'firstname' => $row[2],
                    'email' => $row[1],
                    'seller_id' => $sellerId,
                    'permission_type' => $allPermissions,
                    'seller_phone' => $row[3]
                ]
            ];

            try {
                $rowResult = $this->importSubAccountHelper->importSubAccount($dataForImport);
            }catch (\Exception $e){
                $errorAccount[$row[1]] = $e->getMessage();
            }
            if($rowResult['error']){
                $errorAccount[$row[1]] = $rowResult['message'];
            }
        }
        if(count($errorAccount)){
            $output->writeln('Error account('.count($errorAccount).')');
            foreach($errorAccount as $key => $_err){
                $output->writeln($key);
                $output->writeln($_err);
            }

        }
        return 0;
    }
}