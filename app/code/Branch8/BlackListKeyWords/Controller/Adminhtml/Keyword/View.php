<?php
declare(strict_types=1);

namespace Branch8\BlackListKeyWords\Controller\Adminhtml\Keyword;

use Branch8\BlackListKeyWords\Api\KeywordRepositoryInterface;
use Branch8\BlackListKeyWords\Model\KeywordFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;

/**
 * Controller for the 'blacklist_words/keyword/view' URL route.
 */
class View extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session.
     */
    const ADMIN_RESOURCE = 'Branch8_BlackListKeyWords::manage';

    private PageFactory $resultPageFactory;
    /**
     * @var KeywordRepositoryInterface
     */
    private KeywordRepositoryInterface $keywordRepository;

    private \Magento\Framework\Registry $registry;

    private KeywordFactory $factory;

    /**
     * @param PageFactory $resultPageFactory
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param KeywordFactory $factory
     * @param KeywordRepositoryInterface $keywordRepository
     */
    public function __construct(
        PageFactory                 $resultPageFactory,
        Context                     $context,
        \Magento\Framework\Registry $registry,
        KeywordFactory              $factory,
        KeywordRepositoryInterface  $keywordRepository,
    )
    {
        $this->factory = $factory;
        $this->resultPageFactory = $resultPageFactory;
        $this->keywordRepository = $keywordRepository;
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
                $keyword = $this->keywordRepository->getById($this->getRequest()->getParam('id'));
            } else {
                $keyword = $this->factory->create();
            }
            $this->registry->register('keyword', $keyword);
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->prepend(
                $id ? __('Edit Keyword #%1', $this->getRequest()->getParam('id')) : __('New Keyword')
            );
            return $resultPage;
        } catch (NoSuchEntityException $entityException) {
            $this->messageManager->addError($entityException->getMessage());
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        } catch (\Exception $exception) {
            $this->messageManager->addExceptionMessage($exception);
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }
    }
}


