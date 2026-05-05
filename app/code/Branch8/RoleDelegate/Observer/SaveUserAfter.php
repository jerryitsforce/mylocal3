<?php
namespace Branch8\RoleDelegate\Observer;

use Branch8\RoleDelegate\Helper\Config;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Branch8\RoleDelegate\Model\DelegateFactory;
use Branch8\RoleDelegate\Api\DelegateRepositoryInterface;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Psr\Log\LoggerInterface;

class SaveUserAfter implements ObserverInterface
{
    /*
     * @var RequestInterface
     */
    protected $request;

    /*
     * @var DelegateFactory
     */
    protected $delegateFactory;

   /*
     * @var DelegateRepositoryInterface
     */
    protected $delegateRepository;

    /*
     * @var AuthSession
     */
    protected $authSession;

    /*
     * @var LoggerInterface
     */
    protected $logger;

    /*
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Constructor
     *
     * @param RequestInterface $request
     * @param DelegateFactory $delegateFactory
     * @param DelegateRepositoryInterface $delegateRepository
     * @param AuthSession $authSession
     * @param LoggerInterface $logger
     * @param TimezoneInterface $timezone
     * @param Config $config
     */
    public function __construct(
        RequestInterface $request,
        DelegateFactory $delegateFactory,
        DelegateRepositoryInterface $delegateRepository,
        AuthSession $authSession,
        LoggerInterface $logger,
        TimezoneInterface $timezone,
        Config $config
    ) {
        $this->request = $request;
        $this->delegateFactory = $delegateFactory;
        $this->delegateRepository = $delegateRepository;
        $this->authSession = $authSession;
        $this->logger = $logger;
        $this->timezone = $timezone;
        $this->config = $config;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $user = $observer->getEvent()->getObject();
        $roleId = $this->authSession->getUser()->getRole()->getId();
        if (!in_array($roleId, $this->config->getUserRoles()) || !$user->getId()) {
            return;
        }
        $post = $this->request->getParam('role_delegate', []);
        if (empty($post) || !isset($post['delegate_user_id']) || (!$post['delegate_user_id'] && !$post['start_date'] && !$post['end_date'])) {
            return;
        }
        if (!$post['start_date'] || !$post['end_date']) {
            throw new LocalizedException(__('Please provide start and end date'));
        }
        $delegateUserId = (int)$post['delegate_user_id'];
        $startDate = new \DateTime($post['start_date']);
        $endDate = new \DateTime($post['end_date']);

        if (!$post['delegate_user_id'] && ($post['start_date'] || $post['end_date'])) {
            throw new LocalizedException(__('Please select a delegate'));
        }
        if (!$user->getRoleId() && !$user->getUserRoles()) {
            throw new LocalizedException(__('User must have a role assigned'));
        }
        if ($delegateUserId === (int)$user->getId()) {
            throw new LocalizedException(__('Cannot set same user as delegate'));
        }
        $now = new \DateTime('today');
        if ($startDate < $now) {
            throw new LocalizedException(__('Start date cannot be in the past'));
        }
        if ($endDate < $startDate) {
            throw new LocalizedException(__('End date must be after start date'));
        }

        $delegateId = isset($post['delegate_id']) ? (int)$post['delegate_id'] : 0;
        $startAt = $this->timezone->convertConfigTimeToUtc($post['start_date']);
        $endAt = $this->timezone->convertConfigTimeToUtc($post['end_date']);
        if ($this->delegateRepository->hasOverlap((int)$user->getId(), $delegateId, $startAt, $endAt)) {
            throw new LocalizedException(__('This account already has an assigned agent. Please remove the existing agent before proceeding.'));
        }

        if ($this->delegateRepository->hasOverlapForDelegateUser($delegateUserId, $delegateId, $startAt, $endAt)) {
            throw new LocalizedException(__('Overlapping delegation exists for the selected delegate'));
        }

        try {
            $delegate = $this->delegateFactory->create();
            if ($delegateId) {
                $delegate = $delegate->load((int)$post['delegate_id']);
            } else {
                $userRoleId = $user->getRoleId();
                if (!$userRoleId && $user->getUserRoles()) {
                    $temp = explode('=', $user->getUserRoles());
                    $userRoleId = $temp[0];
                }
                $delegate->setData('user_id', (int)$user->getId());
                $delegate->setData('role_id', $userRoleId);
                $delegate->setData('status', 'pending');
                $delegate->setData('created_by', $this->authSession->getUser()->getId());
            }
            $delegate->setData('delegate_user_id', $delegateUserId);
            $delegate->setData('start_at', $startAt);
            $delegate->setData('end_at', $endAt);
            $delegate->setData('updated_by', $this->authSession->getUser()->getId());

            $this->delegateRepository->save($delegate);
        } catch (\Exception $e) {
            $this->logger->error('RoleDelegate SaveUserAfter error: ' . $e->getMessage());
        }
    }
}
