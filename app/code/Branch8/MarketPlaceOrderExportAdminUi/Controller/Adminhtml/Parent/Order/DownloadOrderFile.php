<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportAdminUi\Controller\Adminhtml\Parent\Order;

use Branch8\MarketPlaceOrderExport\Model\RecordsProvider;
use Branch8\MarketPlaceOrderExport\Model\Writer;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Ui\Component\MassAction\Filter;

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

    /**
     * @param Context $context
     * @param Filter $filter
     * @param DateTime $dateTime
     * @param FileFactory $fileFactory
     * @param RecordsProvider $recordsProvider
     * @param Writer $writer
     */
    public function __construct(
        Context         $context,
        Filter          $filter,
        DateTime        $dateTime,
        FileFactory     $fileFactory,
        RecordsProvider $recordsProvider,
        Writer          $writer
    )
    {
        $this->fileFactory = $fileFactory;
        $this->dateTime = $dateTime;
        $this->recordProvider = $recordsProvider;
        $this->writer = $writer;
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
        /**
         * @var $item \Branch8\MarketPlaceParentOrder\Model\ParentOrder
         */
        $suborderIds = [];
        foreach ($collection as $item) {
            $suborderIds = array_merge($suborderIds, $item->getSuborderIds());
        }
        $suborderIds = array_unique($suborderIds);
        if (!$suborderIds) {
            throw new LocalizedException(__('No sub order ids found'));
        }
        $this->writer->setFileName($fileNameUniqueId)
            ->setOrderIds($suborderIds)
            ->writeHeader()
            ->writeRecords()
            ->save();
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
