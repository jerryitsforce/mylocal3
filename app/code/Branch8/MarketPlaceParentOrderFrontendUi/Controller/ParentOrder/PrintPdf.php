<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder\Pdf\PurchaseReceipt;
use Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder\AbstractController\OrderLoaderInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Controller\OrderInterface;
use Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder\AbstractController\PrintAction as AbstractPrint;
use Magento\Sales\Model\Order\Pdf\Invoice;

/**
 *
 */
class PrintPdf extends AbstractPrint implements OrderInterface
{
    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var Invoice
     */
    protected $purchasePdfReceipt;
    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param OrderLoaderInterface $orderLoader
     * @param PageFactory $resultPageFactory
     * @param DateTime $dateTime
     * @param FileFactory $fileFactory
     * @param PurchaseReceipt $pdfPurchaseReceipt
     * @param Registry $registry
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        OrderLoaderInterface                  $orderLoader,
        PageFactory                           $resultPageFactory,
        DateTime                              $dateTime,
        FileFactory                           $fileFactory,
        PurchaseReceipt                       $pdfPurchaseReceipt,
        Registry                              $registry
    )
    {
        parent::__construct($context, $orderLoader, $resultPageFactory);
        $this->fileFactory = $fileFactory;
        $this->dateTime = $dateTime;
        $this->purchasePdfReceipt = $pdfPurchaseReceipt;
        $this->registry = $registry;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     * @throws \Zend_Pdf_Exception
     */
    public function execute()
    {
        $this->orderLoader->load($this->getRequest());
        $order = $this->registry->registry('current_parent_order');
        $pdf = $this->purchasePdfReceipt->getPdf(
            [$order]
        );
        $fileContent = ['type' => 'string', 'value' => $pdf->render(), 'rm' => true];
        return $this->fileFactory->create(
            sprintf('purchaseReceipt%s.pdf', $this->dateTime->date('Y-m-d_H-i-s')),
            $fileContent,
            DirectoryList::VAR_DIR,
            'application/pdf'
        );
    }
}
