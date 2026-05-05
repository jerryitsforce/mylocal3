<?php
declare(strict_types=1);

namespace Branch8\Rma\ViewModel;

use Branch8\Rma\Model\Actions\ReasonsByTags;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 *
 */
class Reasons implements ArgumentInterface
{

    private ReasonsByTags $reasonByTags;
    private Escaper $esacaper;

    /**
     * @param ReasonsByTags $reasonsBytags
     * @param Escaper $escaper
     */
    public function __construct(
        ReasonsByTags $reasonsBytags,
        Escaper       $escaper
    )
    {
        $this->esacaper = $escaper;
        $this->reasonByTags = $reasonsBytags;
    }

    /**
     * @param string $tag
     * @return array
     */
    public function getReasonByTags(string $tag)
    {
        return $this->reasonByTags->find($tag, [], true, true);
    }

    /**
     * @return array
     */
    public function getReasonsForBuyer()
    {
        $reasons = [];
        $rows = $this->getReasonByTags(ReasonsByTags::FRONTEND_TAG);
        foreach ($rows as $row) {
            $reasons[$row['id']] = $this->esacaper->escapeHtml($row['reason']);
        }
        return $reasons;
    }
}
