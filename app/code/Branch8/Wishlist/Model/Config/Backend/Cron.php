<?php
namespace Branch8\Wishlist\Model\Config\Backend;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\ValidatorException;

class Cron extends Value
{
    public function beforeSave()
    {
        $value = $this->getValue();

        if (!preg_match(
            '/^([\*\/0-9,-]+)\s+([\*\/0-9,-]+)\s+([\*\/0-9,-]+)\s+([\*\/0-9,-]+)\s+([\*\/0-9,-]+)$/',
            $value
        )) {
            throw new ValidatorException(__('Invalid cron expression.'));
        }

        return parent::beforeSave();
    }
}
