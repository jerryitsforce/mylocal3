<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussion\Controller\Adminhtml\Product\Discussions;

use Branch8\MarketPlaceProductDiscussion\Api\ThreadRepositoryInterface;
use Branch8\MarketPlaceProductDiscussion\Model\ThreadFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

/**
 * Controller for the 'catalog/product_discussions/view' URL route.
 */
class View extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session.
     */
    const ADMIN_RESOURCE = 'Branch8_MarketPlaceProductDiscussion::manage';

    private PageFactory $resultPageFactory;
    /**
     * @var ThreadRepositoryInterface
     */
    private ThreadRepositoryInterface $threadRepository;

    private \Magento\Framework\Registry $registry;

    private ThreadFactory $factory;

    /**
     * @param PageFactory $resultPageFactory
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ThreadFactory $factory
     * @param ThreadRepositoryInterface $threadRepository
     */
    public function __construct(
        PageFactory                 $resultPageFactory,
        Context                     $context,
        \Magento\Framework\Registry $registry,
        ThreadFactory               $factory,
        ThreadRepositoryInterface   $threadRepository,
    )
    {
        $this->factory = $factory;
        $this->resultPageFactory = $resultPageFactory;
        $this->threadRepository = $threadRepository;
        $this->registry = $registry;
        parent::__construct($context);
    }
    /**
     * Execute controller action.
     *
     * @return ResponseInterface|ResultInterface
     */
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        try {
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $thread = $this->threadRepository->getById($this->getRequest()->getParam('id'));
            } else {
                $thread = $this->factory->create();
            }
            $this->registry->register('thread', $thread);
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->prepend(
                $id ? __('Edit Thread #%1', $this->getRequest()->getParam('id')) : __('New Thread')
            );
            return $resultPage;
        } catch (NoSuchEntityException $entityException) {
            $this->messageManager->addError($entityException->getMessage());
            return $this->resultRedirectFactory->create()->setPath('catalog/product_discussions/index');
        } catch (\Exception $exception) {
            $this->messageManager->addExceptionMessage($exception);
            return $this->resultRedirectFactory->create()->setPath('catalog/product_discussions/index');
        }
    }
}


