<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       22/03/2026
 */

namespace Branch8\BlackListKeyWords\ViewModel;

use Branch8\BlackListKeyWords\Model\Actions\GetBlackListKeywords;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * @property GetBlackListKeywords $getBlackListKeyWords
 */
class Config implements ArgumentInterface
{
    private GetBlackListKeywords $getBlackListKeyWords;

    /**
     * @param GetBlackListKeywords $getBlackListKeyWords
     */
    public function __construct(GetBlackListKeywords $getBlackListKeyWords)
    {
        $this->getBlackListKeyWords = $getBlackListKeyWords;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->getBlackListKeyWords->get(false);
    }
}
