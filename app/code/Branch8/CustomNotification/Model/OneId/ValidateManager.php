<?php

namespace Branch8\CustomNotification\Model\OneId;

class ValidateManager
{
    private array $rules;

    /**
     * @param array $rules
     */
    public function __construct(
        array $rules = []
    )
    {
        $this->rules = $rules;
    }

    /**
     * @param string $oneId
     * @return array
     */
    public function validate(string $oneId)
    {
        $errors = [];
        $error = false;
        /**
         * @var $rule ValidateInterface
         */
        foreach ($this->rules as $rule) {
            if (!$rule->validate($oneId)) {
                $error = true;
                $errors[] = $rule->getMessageError($oneId)->render();
            };
        }
        $result = ['error' => $error, 'errors' => $errors];
        return $result;
    }
}
