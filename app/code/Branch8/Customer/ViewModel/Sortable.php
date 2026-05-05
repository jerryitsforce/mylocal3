<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       16/04/2026
 */

namespace Branch8\Customer\ViewModel;

use Magento\Customer\Block\Account\SortLinkInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Sortable implements ArgumentInterface
{
    /**
     * @param $links
     * @return array
     */
    public function sortLinks($links = [])
    {
        $sortableLink = [];
        foreach ($links as $key => $link) {
            if ($link instanceof SortLinkInterface) {
                $sortableLink[] = $link;
                unset($links[$key]);
            }
        }
        usort($sortableLink, [$this, "compare"]);
        return array_merge($sortableLink, $links);
    }

    /**
     * @param SortLinkInterface $firstLink
     * @param SortLinkInterface $secondLink
     * @return int
     */
    private function compare(SortLinkInterface $firstLink, SortLinkInterface $secondLink): int
    {
        return $firstLink->getSortOrder() <=> $secondLink->getSortOrder();
    }
}
