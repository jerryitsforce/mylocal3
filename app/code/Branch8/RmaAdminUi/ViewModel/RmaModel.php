<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\ViewModel;
use Branch8\RmaAdminUi\Model\Actions\TotalRmaInReviews;
use Magento\Framework\View\Element\Block\ArgumentInterface;
/**
 *
 */
class RmaModel implements ArgumentInterface
{
    private $totalRmaInReviews;

    /**
     * @param TotalRmaInReviews $totalRmaInReviews
     */
    public function __construct(
        TotalRmaInReviews $totalRmaInReviews
    )
    {
        $this->totalRmaInReviews = $totalRmaInReviews;
    }

    /**
     * @return int
     */
    public function totalInReview()
    {
        return $this->totalRmaInReviews->execute();
    }
}
