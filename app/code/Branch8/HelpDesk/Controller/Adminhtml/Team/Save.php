<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Adminhtml\Team;

use Branch8\HelpDesk\Model\Category;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Branch8\HelpDesk\Model\TeamFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Branch8\HelpDesk\Controller\Adminhtml\Team as AbstractTeam;

/**
 * Save HelpDesk Team Action
 */
class Save extends AbstractTeam implements HttpPostActionInterface
{
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;
    /**
     * @var CategoryFactory|mixed
     */
    private TeamFactory $teamFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param DataPersistorInterface $dataPersistor
     * @param TeamFactory|null $categoryFactory
     */
    public function __construct(
        Context                $context,
        Registry               $coreRegistry,
        DataPersistorInterface $dataPersistor,
        TeamFactory            $categoryFactory = null
    )
    {
        $this->dataPersistor = $dataPersistor;
        $this->teamFactory = $categoryFactory
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(TeamFactory::class);
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
            if (empty($data['team_id'])) {
                $data['team_id'] = null;
            }
            /** @var Category $team */
            $team = $this->teamFactory->create();

            $id = $this->getRequest()->getParam('team_id');
            if ($id) {
                try {
                    $team = $this->teamFactory->create()->load($id);
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(__('This team no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }
            $team->setData($data);
            try {
                $members = [];
                if (!empty($data['members']['assigned_members'])) {
                    foreach ($data['members']['assigned_members'] as $member) {
                        $members[] = $member['user_id'];
                    }
                }
                $team->setMembers($members);
                $team->save();
                $this->messageManager->addSuccessMessage(__('You saved the team.'));
                $this->dataPersistor->clear('team');
                return $this->processReturn($team, $data, $resultRedirect);
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e,
                    __('Something went wrong while saving the team.')
                );
            }
            $this->dataPersistor->set('category', $data);
            return $resultRedirect->setPath('*/*/edit', ['team_id' => $id]);
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
            $resultRedirect->setPath('*/*/edit', ['team_id' => $model->getId()]);
        } elseif ($redirect === 'close') {
            $resultRedirect->setPath('*/*/');
        }
        return $resultRedirect;
    }
}
