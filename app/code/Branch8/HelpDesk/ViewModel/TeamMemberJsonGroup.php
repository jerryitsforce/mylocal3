<?php

declare(strict_types=1);

namespace Branch8\HelpDesk\ViewModel;

use Branch8\HelpDesk\Model\ResourceModel\Team\CollectionFactory as TeamCollectionFactory;
use Branch8\HelpDesk\Model\Team;
use Branch8\HelpDesk\Model\Ticket;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\User\Model\User;

class TeamMemberJsonGroup implements ArgumentInterface
{
    /**
     * @var TeamCollectionFactory
     */
    private TeamCollectionFactory $teamCollectionFactory;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * Constructor.
     *
     * @param Registry $registry
     * @param TeamCollectionFactory $teamCollectionFactory
     */
    public function __construct(
        Registry              $registry,
        TeamCollectionFactory $teamCollectionFactory
    )
    {
        $this->registry = $registry;
        $this->teamCollectionFactory = $teamCollectionFactory;
    }

    /**
     * Gets a list of options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        /** @var Ticket $currentTicket */
        $currentTicket = $this->registry->registry('helpdesk_ticket');
        $teamMembers = $this->getTeamUserRelation();
        $default = [
            0 => ['value' => '', 'label' => __('Please Select Member')->render()]
        ];
        $jsonOptions = $default + $teamMembers;
        return [
            'teamJsonOptions' => $jsonOptions,
            'defaultTeamId' => (string)$currentTicket?->getTeamId(),
            'defaultUserId' => (string)$currentTicket?->getUserId()
        ];
    }

    /**
     * Returns team user relation.
     *
     * @return array
     */
    public function getTeamUserRelation(): array
    {
        $teamMembers = [];
        /** @var Team $team */
        foreach ($this->teamCollectionFactory->create() as $team) {
            $isActive = (bool)$team->getData('is_active');
            if ($isActive === false) {
                continue;
            }
            $members = [];
            /** @var User $member */
            foreach ($team->getTeamMembers() as $member) {
                $members[$member->getId()] = [
                    'value' => $member->getId(),
                    'label' => $member->getName()
                ];
            }
            array_unshift($members,
                ['value' => '', 'label' => __('Please Select Member')->render()]
            );
            $teamMembers[$team->getId()] = $members;
        }
        return $teamMembers;
    }
}
