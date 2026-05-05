<?php

namespace Branch8\CustomNotification\Model\OneId;

interface ValidateInterface
{
    /**
     * @return bool
     */
    public function validate(string $oneId);

    /**
     * @param string $oneId
     * @return mixed
     */
    public function getMessageError(string $oneId);
}
