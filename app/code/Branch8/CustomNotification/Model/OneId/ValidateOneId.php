<?php

namespace Branch8\CustomNotification\Model\OneId;

class ValidateOneId implements ValidateInterface
{
    /**
     * @param string $oneId
     * @return false|int
     */
    public function validate(string $oneId)
    {
        return $this->checkOneId($oneId);
    }

    /**
     * @param string $oneId
     * @return \Magento\Framework\Phrase|mixed
     */
    public function getMessageError(string $oneId)
    {
        return __('Invalid OneID "%1"', $oneId);
    }

    /**
     * @param string $oneId
     * @return false|int
     */
    public function checkOneId(string $oneId)
    {
        return preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/', $oneId);
    }
}
