<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       18/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Controller\Adminhtml\Product\Discussions;

use Branch8\MarketPlaceProductDiscussion\Api\ThreadRepositoryInterface;
use Branch8\MarketPlaceProductDiscussion\Controller\Adminhtml\Product\Discussions;
use Branch8\MarketPlaceProductDiscussion\Model\DiscussionManagement;
use Branch8\MarketPlaceProductDiscussion\Model\Thread;
use Branch8\MarketPlaceProductDiscussion\Model\ThreadFactory;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

class Save extends Discussions implements HttpPostActionInterface
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var ThreadFactory
     */
    private $threadFactory;

    /**
     * @var BlockRepositoryInterface
     */
    private $threadRepository;
    /**
     * @var DiscussionManagement|mixed
     */
    private mixed $discussionManagement;

    private ProductRepository $productRepository;
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @param RequestInterface $request
     * @param Context $context
     * @param LoggerInterface $logger
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param ProductRepository $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param PageFactory|null $resultPageFactory
     * @param ThreadFactory|null $threadFactory
     * @param DiscussionManagement|null $discussionManagement
     * @param ThreadRepositoryInterface|null $threadRepository
     */
    public function __construct(
        RequestInterface            $request,
        Context                     $context,
        LoggerInterface             $logger,
        Registry                    $coreRegistry,
        DataPersistorInterface      $dataPersistor,
        ProductRepository           $productRepository,
        CustomerRepositoryInterface $customerRepository,
        PageFactory                 $resultPageFactory = null,
        ThreadFactory               $threadFactory = null,
        DiscussionManagement        $discussionManagement = null,
        ThreadRepositoryInterface   $threadRepository = null
    )
    {
        $this->dataPersistor = $dataPersistor;
        $this->threadFactory = $threadFactory
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(ThreadFactory::class);
        $this->threadRepository = $threadRepository
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(ThreadRepositoryInterface::class);
        $this->threadRepository = $threadRepository
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(ThreadRepositoryInterface::class);
        $this->discussionManagement = $discussionManagement ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(DiscussionManagement::class);
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        parent::__construct($resultPageFactory ?: ObjectManager::getInstance()->get(PageFactory::class), $request, $context, $logger);
    }

    /**
     * Save action
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue()['general'];
        if ($data) {
            if (empty($data['thread_id'])) {
                $data['thread_id'] = null;
            }

            /**
             * @var $model Thread
             */
            $model = $this->threadFactory->create();
            $id = $data['thread_id'];
            if ($id) {
                try {
                    $model = $this->threadRepository->getById($id);
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(__('This thread no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }
            $model->setData($data);
            try {
                if ($id) {
                    $this->threadRepository->save($model);
                } else {
                    $model = $this->discussionManagement->createThread(
                        $this->productRepository->getById((int)$model->getProductId()),
                        $this->customerRepository->getById((int)$model->getSellerId()),
                        (int)$model->getAuthorId(),
                        $model->getContent()
                    );
                }

                $this->messageManager->addSuccessMessage(__('You saved the thread.'));
                $this->dataPersistor->clear('thread');
                return $this->processBlockReturn($model, $resultRedirect, $this->getRequest()->getParam('back'));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the thread.'));
            }
            $this->dataPersistor->set('thread', $data);
            return $resultRedirect->setPath('*/*/view', ['id' => $id]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param $model
     * @param $resultRedirect
     * @param $back
     * @return mixed
     */
    private function processBlockReturn($model, $resultRedirect, $back = false)
    {
        $redirect = $back ?? 'close';
        if ($redirect === 'edit') {
            $resultRedirect->setPath('*/*/view', ['id' => $model->getId()]);
        } elseif ($redirect === 'close') {
            $resultRedirect->setPath('*/*/');
        }
        return $resultRedirect;
    }
}
