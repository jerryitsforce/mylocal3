<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussion\Controller\Adminhtml\Product\Discussions;

use Branch8\MarketPlaceProductDiscussion\Model\Actions\GetCustomerOptions;
use Branch8\RmaAdminUi\Model\Actions\GetSellerOptions;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Controller for the 'catalog/product_discussions/searchCustomerOptions' URL route.
 */
class SearchCustomerOptions extends Action implements HttpGetActionInterface
{
    private const HTTP_OK = 200;
    private const HTTP_INTERNAL_ERROR = 500;
    private const HTTP_BAD_REQUEST = 400;

    private $logger;
    private GetCustomerOptions $getCustomerOptions;

    /**
     * @param GetSellerOptions $getCustomerOptions
     * @param Context $context
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetCustomerOptions $getCustomerOptions,
        Context            $context,
        LoggerInterface    $logger
    )
    {
        parent::__construct($context);
        $this->getCustomerOptions = $getCustomerOptions;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $searchKey = $this->getRequest()->getParam('searchKey');
        $limit = $this->getRequest()->getParam('limit');
        $pageNum = $this->getRequest()->getParam('page');
        try {
            $filters = [];
            if ($searchKey) {
                $filters[] = ['field' => 'main_table.name LIKE (?)', 'value' => '%' . $searchKey . '%'];
            }
            $responseContent = $this->getCustomerOptions->get($filters, $pageNum, $limit);
            $responseCode = self::HTTP_OK;
        } catch (LocalizedException $exception) {
            $responseCode = self::HTTP_BAD_REQUEST;
            $responseContent = [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        } catch (\Exception $exception) {
            $this->logger->critical($exception);
            $responseCode = self::HTTP_INTERNAL_ERROR;
            $responseContent = [
                'success' => false,
                'message' => __('An error occurred on attempt to get details.'),
            ];
        }

        $resultJson->setHttpResponseCode($responseCode);
        $resultJson->setData($responseContent);

        return $resultJson;
    }
}
