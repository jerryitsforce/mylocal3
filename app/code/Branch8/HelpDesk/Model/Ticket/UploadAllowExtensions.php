<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\OptionSourceInterface;

class UploadAllowExtensions implements OptionSourceInterface
{
    /**
     * ToOptionArray
     * @return array[]
     */
    public function toOptionArray()
    {
        $all = [
            'jpg' => 'JPG',
            'jpeg' => 'JPEG',
            'gif' => 'GIF',
            'png' => 'PNG',
            'csv' => 'CSV',
            'doc' => 'DOC',
            'docx' => 'DOCX',
            'pdf' => 'PDF',
            'mp4' => 'MP4'
        ];
        foreach ($all as $key => $value) {
            $options[] = [
                'value' => $key,
                'label' => $value,
            ];
        }
        return $options;
    }

}
