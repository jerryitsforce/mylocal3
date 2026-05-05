<?php
namespace Branch8\CatalogRule\Block\Adminhtml\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Framework\Escaper;

class ApproveButton extends \Magento\CatalogRule\Block\Adminhtml\Edit\GenericButton implements ButtonProviderInterface
{
    /**
     * Escaper for secure output rendering
     *
     * @var Escaper
     */
    private $escaper;

    protected $request;

    protected $catalogRuleApprovalFactory;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\RequestInterface $request,
        \Branch8\CatalogRule\Model\CatalogRuleApprovalFactory $catalogRuleApprovalFactory
    ) {
        $this->escaper = $context->getEscaper();
        $this->request = $request;
        $this->catalogRuleApprovalFactory = $catalogRuleApprovalFactory;
        parent::__construct($context, $registry);
    }

    /**
     * Get the delete button data
     *
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        $apprId = $this->request->getParam('approval_id');
        if ($apprId) {
            $apprModel = $this->catalogRuleApprovalFactory->create()
            ->load($apprId);
            if($apprModel->getStatus() == \Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_UNDER_REVIEW){
                $data = [
                    'label' => __('Approve'),
                    'class' => 'save primary',
                    'on_click' => '',
                    'sort_order' => 20,
                ];
            }
        }
        return $data;
    }
}
