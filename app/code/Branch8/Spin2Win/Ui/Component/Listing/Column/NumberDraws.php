<?php
declare(strict_types=1);

namespace Branch8\Spin2Win\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Ui\Component\Listing\Columns\Column;
use Branch8\Spin2Win\Model\Config\Source\SegmentType;

/**
 * Class Edit
 */
class NumberDraws extends \Magento\Ui\Component\Listing\Columns\Column{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface                          $context,
        UiComponentFactory                        $uiComponentFactory,
        UrlInterface                              $urlBuilder,
        array                                     $components = [],
        array                                     $data = []
    )
    {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
    public function prepare()
    {
        parent::prepare();
        $config = $this->getData('config');
        $config['dataType'] = 'text'; 
        $this->setData('config', $config);
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
                $item['total_draws'] = number_format((int)$item['total_draws'], 0, '.', ',');
            }
        }
        return $dataSource;
    }
}
