<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\Parent\Order;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Result\PageFactory;
use Branch8\MarketPlaceParentOrderAdminUi\Helper\Logger as LoggerInterface;
use Magento\Backend\App\Action;
class DownLoadPdf extends \Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\ParentOrder
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Branch8_MarketPlaceParentOrderAdminUi::actions_view';

    private FileFactory $fileFactory;

    private DateTime $dateTime;

    /**
     * @param Action\Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param LoggerInterface $logger
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param FileFactory $fileFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        Action\Context                 $context,
        Registry                       $coreRegistry,
        PageFactory                    $resultPageFactory,
        LoggerInterface                $logger,
        ParentOrderRepositoryInterface $parentOrderRepository,
        ParentOrderManagementInterface $parentOrderManagement,
        FileFactory                           $fileFactory,
        DateTime                              $dateTime
    )
    {
        parent::__construct($context,$coreRegistry,$resultPageFactory,$logger,$parentOrderRepository,$parentOrderManagement);
        $this->fileFactory = $fileFactory;
        $this->dateTime = $dateTime;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        /**
         * @var $parentOrder \Branch8\MarketPlaceParentOrder\Model\ParentOrder
         */
        $parentOrder = $this->_initParentOrder();
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($parentOrder) {
            $pdf = ObjectManager::getInstance()->get(
                \Branch8\MarketPlaceParentOrder\Model\ParentOrder\Pdf\PurchaseReceipt::class)
                ->getPdf(
                [$parentOrder]
            );
            $fileName = sprintf('purchaseReceipt%s.pdf', $this->dateTime->date('Y-m-d_H-i-s'));
            $fileContent = ['type' => 'string', 'value' => $pdf->render(), 'rm' => true];
            return $this->fileFactory->create(
                $fileName,
                $fileContent,
                DirectoryList::VAR_DIR,
                'application/pdf'
            );
        }
        $resultRedirect->setPath('sales/*/');
        return $resultRedirect;
    }
}
