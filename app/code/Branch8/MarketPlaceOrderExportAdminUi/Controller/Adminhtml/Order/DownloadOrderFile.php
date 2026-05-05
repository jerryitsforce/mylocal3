<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportAdminUi\Controller\Adminhtml\Order;

use Branch8\MarketPlaceOrderExport\Model\RecordsProvider;
use Branch8\MarketPlaceOrderExport\Model\Writer;
use Branch8\MarketPlaceOrderExport\Model\Writer2;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;

/**
 *
 */
class DownloadOrderFile extends ExcelDocumentsMassAction
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Magento_Sales::sales_download_order_file';

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var DateTime
     */
    protected $dateTime;
    /**
     * @var RecordsProvider
     */
    protected $recordProvider;
    /**
     * @var Filesystem
     */
    protected $filesystem;
    private Writer $writer;
    private Writer2 $writer2;
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param Context $context
     * @param \Branch8\MarketPlaceOrderExportAdminUi\Model\Filter $filter
     * @param DateTime $dateTime
     * @param FileFactory $fileFactory
     * @param RecordsProvider $recordsProvider
     * @param ScopeConfigInterface $config
     * @param Writer $writer
     * @param Writer2 $writer2
     */
    public function __construct(
        Context                                             $context,
        \Branch8\MarketPlaceOrderExportAdminUi\Model\Filter $filter,
        DateTime                                            $dateTime,
        FileFactory                                         $fileFactory,
        RecordsProvider                                     $recordsProvider,
        ScopeConfigInterface                                $config,
        Writer                                              $writer,
        Writer2                                             $writer2
    )
    {
        $this->fileFactory = $fileFactory;
        $this->dateTime = $dateTime;
        $this->recordProvider = $recordsProvider;
        $this->writer = $writer;
        $this->writer2 = $writer2;
        $this->scopeConfig = $config;
        parent::__construct($context, $filter);
    }

    /**
     * Print credit memos for selected orders
     *
     * @param AbstractCollection $collection
     * @return ResponseInterface|ResultInterface
     * @throws \Exception
     */
    protected function massAction(AbstractCollection $collection)
    {
        $date = $this->dateTime->date('Y-m-d_H-i-s');
        $fileName = sprintf('orders-%s.xlsx', $date);
        $fileNameUniqueId = sprintf('orders-%s.xlsx', uniqid());
        $useOptimize = (bool)$this->scopeConfig->getValue('order_export/optimize/use_writer2');
        $useStream = (bool)$this->scopeConfig->getValue('order_export/optimize/use_stream');

        if ($useOptimize) {
            $this->writer2->initCache();
            if ($useStream) {
                $this->writer2->setIsstream(true);
            }
            $this->writer2->setFileName($fileNameUniqueId)
                ->setOrderIds($collection->getAllIds())
                ->writeHeader()
                ->writeRecords()
                ->save();
        } else {
            $this->writer->setFileName($fileNameUniqueId)
                ->setOrderIds($collection->getAllIds())
                ->writeHeader()
                ->writeRecords()
                ->save();
        }

        $content = [
            'type' => 'filename',
            'value' => $this->writer->getFilePath(),
            'rm' => 1
        ];
        return $this->fileFactory->create(
            $fileName,
            $content,
            DirectoryList::VAR_EXPORT,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }
}
