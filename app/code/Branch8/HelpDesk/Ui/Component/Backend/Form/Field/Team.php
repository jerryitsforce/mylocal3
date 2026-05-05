<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Ui\Component\Backend\Form\Field;

use Branch8\HelpDesk\ViewModel\TeamMemberJsonGroup;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Sanitizer;
use Magento\Framework\View\Element\UiComponentFactory;

class Team extends \Magento\Ui\Component\Form\Field
{
    private $teamMemberJsonGroup;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param TeamMemberJsonGroup $teamMemberJsonGroup
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface                           $context,
        UiComponentFactory                         $uiComponentFactory,
        TeamMemberJsonGroup                        $teamMemberJsonGroup,
        array                                      $components = [],
        array                                      $data = []
    )
    {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->teamMemberJsonGroup = $teamMemberJsonGroup;
    }

    /**
     * @inheritdoc
     */
    public function prepare()
    {
        parent::prepare();
        if (empty($this->_data['config']['indexedOptions'])) {
            $this->_data['config']['teamOptions'] = $this->teamMemberJsonGroup->getTeamUserRelation();
        }
    }
}
