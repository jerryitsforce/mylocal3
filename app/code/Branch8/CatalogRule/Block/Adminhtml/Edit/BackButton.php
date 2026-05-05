<?php
namespace Branch8\CatalogRule\Block\Adminhtml\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Framework\Escaper;

class BackButton extends \Magento\CatalogRule\Block\Adminhtml\Edit\GenericButton implements ButtonProviderInterface
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
        $data = [
            'label' => __('Back'),
            'class' => 'back',
            'on_click' => 'window.location.href="'.$this->urlBuilder->getUrl('promocatalog/history/index/', ['catalogrule_id' => $this->request->getParam('id')]).'"',
            'sort_order' => 20,
        ];
        return $data;
    }
}
