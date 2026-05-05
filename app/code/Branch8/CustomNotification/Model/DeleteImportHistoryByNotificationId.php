<?php

namespace Branch8\CustomNotification\Model;

use Magento\Framework\App\ResourceConnection;

class DeleteImportHistoryByNotificationId
{
    private ResourceConnection $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource
    )
    {
        $this->resource = $resource;
    }

    /**
     * @param $notificationId
     * @return $this
     */
    public function execute($notificationId)
    {
        $column = [
            'imported_file'
        ];
        $select = $this->resource->getConnection()->select()->from(
            'magenest_notification_oneid_import_history',
            $column
        )->where('notification_id = ?', $notificationId);
        $rows = $this->resource->getConnection()->fetchAll($select);

        if ($rows) {
            foreach ($rows as $row) {
                $path = BP . '/var/OneIdList' . $row['imported_file'];
                @unlink($path);
            }
        }
        $this->resource->getConnection()->delete(
            $this->resource->getTableName('magenest_notification_oneid_import_history'),
            ['notification_id = ?' => $notificationId]
        );
        return $this;
    }
}
