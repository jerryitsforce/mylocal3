<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussionCustomer\Controller\Ajax;

use Branch8\BlackListKeyWords\Api\KeywordFilterInterface;
use Branch8\MarketPlaceProductDiscussion\Api\DiscussionManagementInterface;
use Branch8\MarketPlaceProductDiscussionCustomer\Model\Actions\GetSellerByProduct;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;

/**
 * Controller for the 'product_discussion/ajax/postThread' URL route.
 */
class PostThread implements HttpPostActionInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private \Magento\Customer\Model\Session $customerSession;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private \Magento\Framework\Json\Helper\Data $helper;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory;
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    private \Magento\Framework\Controller\Result\RawFactory $resultRawFactory;

    private RequestInterface $request;
    private DiscussionManagementInterface $discussionManagement;
    private ProductRepositoryInterface $productRepository;
    private GetSellerByProduct $getSellerByProduct;
    private $messageManager;

    private KeywordFilterInterface $blackListKeyWordsFilter;

    private UrlInterface $url;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param DiscussionManagementInterface $discussionManagement
     * @param GetSellerByProduct $getSellerIdByProductId
     * @param ProductRepositoryInterface $productRepository
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param KeywordFilterInterface $blackListKeyWordsFilter
     * @param UrlInterface $url
     */
    public function __construct(
        \Magento\Customer\Model\Session                  $customerSession,
        \Magento\Framework\Json\Helper\Data              $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory  $resultRawFactory,
        DiscussionManagementInterface                    $discussionManagement,
        GetSellerByProduct                               $getSellerIdByProductId,
        ProductRepositoryInterface                       $productRepository,
        RequestInterface                                 $request,
        ManagerInterface                                 $messageManager,
        KeywordFilterInterface                           $blackListKeyWordsFilter,
        UrlInterface                                     $url
    )
    {
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->request = $request;
        $this->discussionManagement = $discussionManagement;
        $this->getSellerByProduct = $getSellerIdByProductId;
        $this->productRepository = $productRepository;
        $this->messageManager = $messageManager;
        $this->blackListKeyWordsFilter = $blackListKeyWordsFilter;
        $this->url = $url;
    }

    /**
     * Execute controller action.
     */
    public function execute()
    {
        $httpBadRequestCode = 400;
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        try {
            $postDetail = $this->helper->jsonDecode($this->request->getContent());
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        if (!$postDetail || $this->request->getMethod() !== 'POST'
            || !$this->request->isXmlHttpRequest()
            || !$this->customerSession->isLoggedIn()
        ) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        $response = [
            'errors' => false,
            'messages' => [__('Question was successfully sent')]
        ];
        try {
            $product = $this->productRepository->getById((int)$postDetail['productId']);
            $blackList = $this->blackListKeyWordsFilter->getMatchedKeywords($postDetail['content'], true);
            if ($blackList) {
                throw new InputException(__('Your question contains black list words (%1),please remove and try again', $blackList));
            }
            $this->discussionManagement->createThread(
                $product,
                $this->getSellerByProduct->get((int)$product->getId()),
                (int)$this->customerSession->getCustomerId(),
                $postDetail['content']
            );
            $this->messageManager->addSuccessMessage(__('Question was successfully sent'));
        } catch (InputException $exception) {
            $response = [
                'errors' => true,
                'messages' => [],
            ];
            $response['messages'][$exception->getMessage()] = $exception->getMessage();
            foreach ($exception->getErrors() as $error) {
                $response['messages'][$error->getMessage()] = $error->getMessage();
            }
            $response['messages'] = array_values(array_unique($response['messages']));
            foreach ($response['messages'] as $message) {
                $this->messageManager->addErrorMessage($message);
            }
        } catch (LocalizedException $e) {
            $response = [
                'errors' => true,
                'messages' => [$e->getMessage()],
            ];
            foreach ($response['messages'] as $message) {
                $this->messageManager->addErrorMessage($message);
            }
        } catch (\Exception $e) {
            $response = [
                'errors' => true,
                'messages' => [__('Error when creating question')],
            ];
            foreach ($response['messages'] as $message) {
                $this->messageManager->addErrorMessage($message);
            }
        }
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $response = $this->getBackUrl($response, $postDetail, $product);
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }

    /**
     * @return void
     */
    private function getBackUrl($response, $postDetail,\Magento\Catalog\Model\Product $product)
    {
        $defaultBackUrl = $product->getProductUrl() . '#discussions';
        $response['redirectUrl'] = $defaultBackUrl;
        if (isset($postDetail['referer']) && $postDetail['referer'] === 'list') {
            $response['redirectUrl'] = $this->url->getUrl('product_discussion/thread/list',
                ['sku' => $product->getSKu(), 'referer' => $postDetail['referer']]
            );
        }
        return $response;
    }
}
