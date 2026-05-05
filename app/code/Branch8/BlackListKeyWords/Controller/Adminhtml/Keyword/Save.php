<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       18/03/2026
 */

namespace Branch8\BlackListKeyWords\Controller\Adminhtml\Keyword;

use Branch8\BlackListKeyWords\Api\KeywordRepositoryInterface;
use Branch8\BlackListKeyWords\Controller\Adminhtml\Keyword;
use Branch8\BlackListKeyWords\Model\Keyword as ModelKeyword;
use Branch8\BlackListKeyWords\Model\KeywordFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;

/**
 * Controller for the 'blacklist_words/keyword/save' URL route.
 */
class Save extends Keyword implements HttpPostActionInterface
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;
    private ?KeywordFactory $keywordFactory;
    private KeywordRepositoryInterface $keywordRepository;

    /**
     * @param RequestInterface $request
     * @param Context $context
     * @param LoggerInterface $logger
     * @param DataPersistorInterface $dataPersistor
     * @param KeywordRepositoryInterface $keywordRepository
     * @param PageFactory|null $resultPageFactory
     * @param KeywordFactory $keywordFactory
     */
    public function __construct(
        RequestInterface           $request,
        Context                    $context,
        LoggerInterface            $logger,
        DataPersistorInterface     $dataPersistor,
        KeywordRepositoryInterface $keywordRepository,
        PageFactory                $resultPageFactory = null,
        KeywordFactory             $keywordFactory

    )
    {
        $this->keywordRepository = $keywordRepository;
        $this->dataPersistor = $dataPersistor;
        $this->keywordFactory = $keywordFactory;
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
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            if (empty($data['entity_id'])) {
                $data['entity_id'] = null;
            }

            /**
             * @var $model \Branch8\BlackListKeyWords\Model\Keyword
             */
            $model = $this->keywordFactory->create();
            $id = $data['entity_id'];
            if ($id) {
                try {
                    $model = $this->keywordRepository->getById($id);
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(__('This thread no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }
            $model->setData($data);
            try {
                $this->keywordRepository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the keyword.'));
                $this->dataPersistor->clear('keyword');
                return $this->processBlockReturn($model, $resultRedirect, $this->getRequest()->getParam('back'));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the keyword.'));
            }
            $this->dataPersistor->set('keyword', $data);
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
