<?php
namespace Branch8\HotaiPoint\Block\Detail;

class AbstractBlock extends \Magento\Framework\View\Element\Template
{
    protected $pointHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Branch8\HotaiPoint\Helper\Data $pointHelper,
        array $data = []
    ) {
        $this->pointHelper = $pointHelper;
        parent::__construct($context, $data);
    }

    public function getPointHelper()
    {
        return $this->pointHelper;
    }

    public function createDateFromFormat(string $date, $format = 'Ymd')
    {
        return \DateTime::createFromFormat($format, $date);
    }

    /**
     * Sanitize the input to prevent XSS and SQL Injection
     *
     * @param mixed $input
     * @return mixed
     */
    protected function sanitizeInput($input)
    {
        if (is_array($input)) {
            foreach ($input as &$value) {
                $value = $this->sanitizeInput($value);
            }
            return $input;
        }

        // Trim and remove HTML special characters
        if(!empty($input)) {
            $input = trim($input);
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            $input = filter_var($input, FILTER_SANITIZE_SPECIAL_CHARS);
            $input = htmlspecialchars($input);
        }

        return $input;
    }
}
