<?php
namespace Branch8\GA4\Plugin;

class SessionManager
{
    /**
     * @param SessionManager $subject
     * @param string $method
     * @param array $args
     * @return array
     */
    public function before__call(\Magento\Framework\Session\SessionManager $subject, $method, $args): array
    {
        if (str_starts_with(strtolower($method), 'setga4') && count($args) === 1 && is_array($args[0])) {
            if ($method === 'setGA4CheckoutOptionsData') {
                foreach ($args[0] as $key => $value) {
                    $value['uid'] = uniqid('evt');
                    $args[0][$key] = $value;
                }
            } else {
                $args[0]['uid'] = uniqid('evt');
            }
        }
        // TODO: Implement plugin method.
        return [$method, $args];
    }
}
