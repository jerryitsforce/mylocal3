<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Ui\Component\Listing\Columns;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class CustomerName
 * @package Branch8\Blog\Ui\Component\Listing\Columns
 */
class CustomerName extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if ($item['entity_id'] == 0) {
                    $item['customer_name'] = '<span>' . $item['user_name'] . ' (Guest)</span>';
                }
            }
        }

        return $dataSource;
    }
}
