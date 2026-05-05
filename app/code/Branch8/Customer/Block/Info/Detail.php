<?php

declare(strict_types=1);

namespace Branch8\Customer\Block\Info;

class Detail extends Index
{
    /**#@+
     * Constants for info detail.
     */
    public const TAB_BASIC_INFO = 'basic';
    public const TAB_MEMBER_NICKNAME = 'nickname';
    public const TAB_INVOICE_CARRIER = 'invoice_carrier';

    /**
     * Check if active tab.
     *
     * @param string $tab
     *
     * @return bool
     */
    public function isActiveTab(string $tab): bool
    {
        return $this->getCurrentTab() === $tab;
    }

    /**
     * Returns current tab.
     *
     * @return string
     */
    public function getCurrentTab(): string
    {
        return (string) $this->getRequest()->getParam('tab', self::TAB_BASIC_INFO);
    }

    /**
     * Returns current tab by number
     *
     * @return int
     */
    public function getCurrentTabByNum()
    {
        if($this->getCurrentTab() == self::TAB_MEMBER_NICKNAME) {
            return 1;
        } elseif($this->getCurrentTab() == self::TAB_INVOICE_CARRIER) {
            return 2;
        }
        return 0;
    }

    /**
     * Build tab URL.
     *
     * @param string $tab
     *
     * @return string
     */
    public function getTabUrl(string $tab): string
    {
        return $this->getUrl('*/*/*', ['_current' => false, '_use_rewrite' => true, '_query' => ['tab' => $tab]]);
    }
}

