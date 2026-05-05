<?php
declare(strict_types=1);

namespace Branch8\WebkulMpsplitorder\Model\VerifyQuote;

class VerifyQuoteComposite
{
    private array $composite;

    /**
     * @param array $composite
     */
    public function __construct(
        array $composite
    )
    {
        $this->composite = $composite;
    }

    /**
     * @param $code
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param \Magento\Sales\Model\AdminOrder\Create $adminOrderModel
     * @return VerifyResult
     */
    public function verify(
        $code,
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Magento\Sales\Model\AdminOrder\Create $adminOrderModel
    )
    {

        $result = new VerifyResult(
            [
                'result' => true,
                'error' => ''
            ]
        );
        if (isset($this->composite[$code]) &&
            $this->composite[$code] instanceof VerifyQuoteActionInterface
        ) {
            $verifyAction = $this->composite[$code];
            $result = $verifyAction->verify($quoteSession, $adminOrderModel);
        }
        return $result;
    }
}
