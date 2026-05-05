<?php
namespace Branch8\SellerContactInformation\Console\Command;

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
use Magento\User\Model\UserFactory as AdminUserFactory;
use Magento\User\Model\ResourceModel\User\CollectionFactory as AdminUserCollectionFactory;

/**
 * Class ImportAdminUser
 * @package Branch8\SellerContactInformation\Console\Command
 */
class ImportAdminUser extends Command
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
     * @var AdminUserFactory
    */
    protected $adminUserFactory;

     /**
     * @var AdminUserCollectionFactory
    */
    protected $adminUserCollectionFactory;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    public $logger;

    public function __construct(
        State $appState,
        DirectoryList $directoryList,
        Csv $csv,
        File $file,
        StoreManagerInterface $storeManager,
        CustomerInterfaceFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        EncryptorInterface $encryptor,
        AdminUserFactory $adminUserFactory,
        AdminUserCollectionFactory $adminUserCollectionFactory,
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
        $this->adminUserFactory = $adminUserFactory;
        $this->adminUserCollectionFactory = $adminUserCollectionFactory;
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('hotai:import_admin_user');
        $this->setDescription('Import admin user');
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

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/command_import_update_admin.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Start import admin user');

        $csvFilePath = 'pub/media/marketplace/manager.csv';
        $rootDirectory = $this->directoryList->getRoot();
        $csvFile = $rootDirectory . "/" . $csvFilePath;

        $adminRoleFilePath = 'pub/media/marketplace/roles.csv';
        $adminRoleFile = $rootDirectory . "/" . $adminRoleFilePath;

        try {
            if ($this->file->isExists($csvFile) && $this->file->isExists($adminRoleFile)) {
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
                    $arrayRolesId[$row['Role']] = $row['MagentoRoleId'];
                }

                // Read manager.csv
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
                    $adminId = '';
                    $skipRow = false;
                    $uniqueEmail = trim($row['Email']);
                    $username = substr($uniqueEmail, 0, strrpos($uniqueEmail, '@'));

                    //find admin by email, if not found => create admin user
                    $adminUserModel = $this->adminUserFactory->create();
                    $adminUserModel->loadByUsername($username);
                    if($adminUserModel->getId()) {
                        $adminId = $adminUserModel->getId();
                    }

                    try {
                        $admin = $this->adminUserFactory->create();
                        if (!empty($adminId)) {
                            $admin->load($adminId);
                        } else {
                            $admin->setUsername($username);
                            $admin->setEmail($uniqueEmail);
                        }
                        $admin->setWebsiteId($websiteId);
                        $admin->setFirstname($row['MType']);
                        $admin->setLastname($row['Name']);
                        $admin->setPassword($row['Password']);
                        $admin->setIsActive($row['IsEnabled']);
                        $admin->setData('interface_locale', 'en_US');
                        $roleId = isset($arrayRolesId[$row['MRole']]) ? (int) $arrayRolesId[$row['MRole']] : 1;
                        $admin->setRoleId($roleId);
                        $admin->save();
                        $logger->info('Success create admin user ' . $uniqueEmail);
                    } catch (\Exception $e) {
                        $logger->info('Error when create admin user ' . $uniqueEmail);
                        $logger->info($e->getMessage());
                        if ($e->getMessage() == 'Your password must include both numeric and alphabetic characters.') {
                            try {
                                $admin->setPassword('Hotai@123');
                                $admin->save();
                                $logger->info('Success create admin user ' . $uniqueEmail . ' with default password');
                            } catch (\Exception $e) {
                                $logger->info($e->getMessage());
                            }
                        }

                        $skipRow = true;
                    }

                    if ($skipRow) {
                        continue;
                    }
                }
            } else {
                $logger->info('Csv file not exist');
            }
        } catch (\Exception $e) {
            $logger->info('Error when import/update admin user');
            $logger->info($e->getMessage());
        }
        return 1;
    }
}
