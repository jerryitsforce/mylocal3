<?php
declare(strict_types=1);

namespace Branch8\BlackListKeyWords\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class ViewAction
 */
class Actions extends Column
{
    /** Url path */
    const EDIT_URL = 'blacklist_words/keyword/view';
    /**
     * @var string
     */
    private $editUrl;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param array $components
     * @param array $data
     * @param string $editUrl
     */
    public function __construct(
        ContextInterface                          $context,
        UiComponentFactory                        $uiComponentFactory,
        UrlInterface                              $urlBuilder,
        \Magento\Framework\AuthorizationInterface $authorization,
        array                                     $components = [],
        array                                     $data = [],
        string                                    $editUrl = self::EDIT_URL
    )
    {
        $this->authorization = $authorization;
        $this->urlBuilder = $urlBuilder;
        $this->editUrl = $editUrl;
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
                $name = $this->getData('name');
                if (isset($item['entity_id'])) {
                    $item[$name]['edit'] = [
                        'href' => $this->urlBuilder->getUrl(
                            self::EDIT_URL, ['id' => $item['entity_id']]
                        ),
                        'label' => __('View')
                    ];
                }
            }
        }
        return $dataSource;
    }
}
