<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Escaper;

/**
 * Class CommentContent
 * @package Branch8\Blog\Ui\Component\Listing\Columns
 */
class CommentContent extends Column
{
    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * CommentContent constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Escaper $escaper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        $this->escaper = $escaper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $limitContent = 150;
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$this->getData('name')])) {
                    $content = $this->escaper->escapeHtml($item['content']);
                    if (strlen($content) > $limitContent) {
                        $content = mb_substr($content, 0, $limitContent, 'UTF-8') . '.....';
                    }
                    $item[$this->getData('name')] = '<span>' . $content . '</span>';
                }
            }
        }

        return $dataSource;
    }
}
