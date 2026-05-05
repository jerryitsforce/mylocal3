<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Adminhtml\Category;

use Branch8\HelpDesk\Model\Category;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Branch8\HelpDesk\Model\CategoryFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Branch8\HelpDesk\Controller\Adminhtml\Category as AbstractCategory;

/**
 * Save HelpDesk Category Action
 */
class Save extends AbstractCategory implements HttpPostActionInterface
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;
    /**
     * @var CategoryFactory|mixed
     */
    private mixed $categoryFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param CategoryFactory|null $categoryFactory
     */
    public function __construct(
        Context                $context,
        Registry               $coreRegistry,
        DataPersistorInterface $dataPersistor,
        CategoryFactory        $categoryFactory = null
    )
    {
        $this->dataPersistor = $dataPersistor;
        $this->categoryFactory = $categoryFactory
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(CategoryFactory::class);
        parent::__construct($context, $coreRegistry);
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
            if (isset($data['is_active']) && $data['is_active'] === 'true') {
                $data['is_active'] = Category::ENABLE;
            }
            if (empty($data['category_id'])) {
                $data['category_id'] = null;
            }
            /** @var Category $model */
            $model = $this->categoryFactory->create();

            $id = $this->getRequest()->getParam('category_id');
            if ($id) {
                try {
                    $model = $this->categoryFactory->create()->load($id);
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(__('This category no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }
            $model->setData($data);
            try {
                $model->save();
                $this->messageManager->addSuccessMessage(__('You saved the category.'));
                $this->dataPersistor->clear('category');
                return $this->processReturn($model, $data, $resultRedirect);
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e,
                    __('Something went wrong while saving the category.')
                );
            }

            $this->dataPersistor->set('category', $data);
            return $resultRedirect->setPath('*/*/edit', ['category_id' => $id]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param $model
     * @param $data
     * @param $resultRedirect
     * @return mixed
     */
    private function processReturn($model, $data, $resultRedirect)
    {
        $redirect = $data['back'] ?? 'close';
        if ($redirect === 'continue') {
            $resultRedirect->setPath('*/*/edit', ['category_id' => $model->getId()]);
        } elseif ($redirect === 'close') {
            $resultRedirect->setPath('*/*/');
        }
        return $resultRedirect;
    }
}
