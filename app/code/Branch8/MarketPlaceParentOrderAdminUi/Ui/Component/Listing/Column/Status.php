<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Ui\Component\Listing\Column;

use Branch8\MarketPlaceParentOrderAdminUi\Model\Config;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Status extends Column
{
    private $config;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Config $config
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        Config             $config,
        array              $components = [],
        array              $data = []
    )
    {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->config = $config;
    }

    /**
     * @inheritdoc
     * @since 101.1.1
     */
    public function prepare()
    {
        $config = $this->getData('config');
        if (!$this->config->showStatus()) {
            $config['componentDisabled'] = true;
        }
        $this->setData('config', $config);

        parent::prepare();
    }
}
