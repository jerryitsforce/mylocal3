<?php
declare(strict_types=1);

namespace Branch8\WebkulMpsplitorder\Model;

use Magento\Framework\DataObject\Copy;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterfaceFactory as OrderFactory;
use Magento\Sales\Model\Order;

/**
 * This class will take responsibility to copy origin quote to new quote
 */
class TransferMasterQuoteDataToSubQuoteDataService
{
    /**
     * @var Copy
     */
    protected $objectCopyService;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @param OrderFactory $orderFactory
     * @param Copy $objectCopyService
     * @param ManagerInterface $eventManager
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        OrderFactory                            $orderFactory,
        Copy                                    $objectCopyService,
        ManagerInterface                        $eventManager,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
    )
    {
        $this->orderFactory = $orderFactory;
        $this->objectCopyService = $objectCopyService;
        $this->eventManager = $eventManager;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * @param Quote $masterQuote
     * @param Quote $subQuote
     * @return Quote
     */
    public function transfer(Quote $masterQuote, Quote $subQuote)
    {
        $this->objectCopyService->copyFieldsetToTarget(
            'sales_convert_master_quote_to_sub_quote',
            'to_sub_quote',
            $masterQuote,
            $subQuote
        );
        $this->eventManager->dispatch(
            'sales_transfer_data__master_quote_to_sub_quote',
            ['sub_quote' => $subQuote, 'master_quote' => $masterQuote]
        );
        return $subQuote;
    }
}
