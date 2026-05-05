<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Ui\Component\Backend\Listing\Column;

use Branch8\MaskCustomerInformation\Model\AdminPermission;
use Branch8\MaskInformation\Api\MaskRulesCompositeInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

/**
 *
 */
class CustomerEmail extends Column
{

    /** @var UrlInterface */
    protected $urlBuilder;
    private $authorization;

    private $adminPermision;

    private $maskRuleComposite;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param AdminPermission $adminPermission
     * @param MaskRulesCompositeInterface $maskRulesComposite
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface                          $context,
        UiComponentFactory                        $uiComponentFactory,
        \Magento\Framework\AuthorizationInterface $authorization,
        AdminPermission                           $adminPermission,
        MaskRulesCompositeInterface               $maskRulesComposite,
        array                                     $components = [],
        array                                     $data = []
    )
    {
        $this->authorization = $authorization;
        $this->adminPermision = $adminPermission;
        $this->maskRuleComposite = $maskRulesComposite;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (!$this->adminPermision->canView()) {
                    $masked = $this->maskRuleComposite->mask('email',
                        $item['customer_email']
                    );
                    $item['customer_email'] = $masked;
                }

            }
        }
        return $dataSource;
    }
}
