<?php
/**
 * Override Calendar class to fix null array offset warning
 */
declare(strict_types=1);

namespace Branch8\MagentoUi\Override\Magento\Framework\View\Element\Html;

use Magento\Framework\Locale\Bundle\DataBundle;

class Calendar extends \Magento\Framework\View\Element\Html\Calendar
{
    /**
     * Render block HTML with null checks to prevent warnings
     *
     * @return string
     */
    protected function _toHtml()
    {
        $localeData = (new DataBundle())->get($this->_localeResolver->getLocale());

        // Check if localeData is null or doesn't have required structure
        if (!$localeData) {
            return $this->_getTemplateHtml();
        }

        $calendar = $localeData->get('calendar');
        if (!$calendar) {
            return $this->_getTemplateHtml();
        }

        $gregorian = $calendar->get('gregorian');
        if (!$gregorian) {
            return $this->_getTemplateHtml();
        }

        // get days names
        $dayNames = $gregorian->get('dayNames');
        if ($dayNames) {
            $format = $dayNames->get('format');
            if ($format) {
                $wide = $format->get('wide');
                $abbreviated = $format->get('abbreviated');
                if ($wide && $abbreviated) {
                    $this->assign(
                        'days',
                        [
                            'wide' => $this->encoder->encode(array_values(iterator_to_array($wide))),
                            'abbreviated' => $this->encoder->encode(
                                array_values(iterator_to_array($abbreviated))
                            ),
                        ]
                    );
                }
            }
        }

        /**
         * Month names in abbreviated format values was added to ICU Data tables
         * starting ICU library version 52.1. For some OS, like CentOS, default
         * installation version of ICU library is 50.1.2, which not contain
         * 'abbreviated' key, and that may cause a PHP fatal error when passing
         * as an argument of function 'iterator_to_array'. This issue affects
         * locales like ja_JP, ko_KR etc.
         *
         * @see http://source.icu-project.org/repos/icu/tags/release-50-1-2/icu4c/source/data/locales/ja.txt
         * @see http://source.icu-project.org/repos/icu/tags/release-52-1/icu4c/source/data/locales/ja.txt
         * @var \ResourceBundle $monthsData
         */
        $monthNames = $gregorian->get('monthNames');
        if ($monthNames) {
            $format = $monthNames->get('format');
            if ($format) {
                $wide = $format->get('wide');
                if ($wide) {
                    $abbreviated = $format->get('abbreviated');
                    $this->assign(
                        'months',
                        [
                            'wide' => $this->encoder->encode(array_values(iterator_to_array($wide))),
                            'abbreviated' => $this->encoder->encode(
                                array_values(
                                    iterator_to_array(
                                        $abbreviated ? $abbreviated : $wide
                                    )
                                )
                            ),
                        ]
                    );
                }
            }
        }

        $this->assignFieldsValues($localeData);

        // get "am" & "pm" words - Fix for line 114 warning
        $ampmMarkers = $gregorian->get('AmPmMarkers');
        if ($ampmMarkers) {
            $am = $ampmMarkers->get('0');
            $pm = $ampmMarkers->get('1');
            if ($am !== null) {
                $this->assign('am', $this->encoder->encode($am));
            } else {
                $this->assign('am', $this->encoder->encode('AM'));
            }
            if ($pm !== null) {
                $this->assign('pm', $this->encoder->encode($pm));
            } else {
                $this->assign('pm', $this->encoder->encode('PM'));
            }
        } else {
            // Set default values if AmPmMarkers is null
            $this->assign('am', $this->encoder->encode('AM'));
            $this->assign('pm', $this->encoder->encode('PM'));
        }

        // get first day of week and weekend days
        $this->assign(
            'firstDay',
            (int)$this->_scopeConfig->getValue(
                'general/locale/firstday',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );
        $this->assign(
            'weekendDays',
            $this->encoder->encode(
                (string)$this->_scopeConfig->getValue(
                    'general/locale/weekend',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )
        );

        // define default format and tooltip format
        $this->assign(
            'defaultFormat',
            $this->encoder->encode(
                $this->_localeDate->getDateFormat(\IntlDateFormatter::MEDIUM)
            )
        );
        $this->assign(
            'toolTipFormat',
            $this->encoder->encode(
                $this->_localeDate->getDateFormat(\IntlDateFormatter::LONG)
            )
        );

        // get days and months for en_US locale - calendar will parse exactly in this locale
        $englishLocaleData = (new DataBundle())->get('en_US');
        if ($englishLocaleData) {
            $englishCalendar = $englishLocaleData->get('calendar');
            if ($englishCalendar) {
                $englishGregorian = $englishCalendar->get('gregorian');
                if ($englishGregorian) {
                    $englishMonths = $englishGregorian->get('monthNames');
                    if ($englishMonths) {
                        $englishFormat = $englishMonths->get('format');
                        if ($englishFormat) {
                            $englishWide = $englishFormat->get('wide');
                            if ($englishWide) {
                                $enUS = new \stdClass();
                                $enUS->m = new \stdClass();
                                $enUS->m->wide = array_values(iterator_to_array($englishWide));
                                $englishAbbreviated = $englishFormat->get('abbreviated');
                                if ($englishAbbreviated) {
                                    $enUS->m->abbr = array_values(iterator_to_array($englishAbbreviated));
                                } else {
                                    $enUS->m->abbr = $enUS->m->wide;
                                }
                                $this->assign('enUS', $this->encoder->encode($enUS));
                            }
                        }
                    }
                }
            }
        }

        return $this->_getTemplateHtml();
    }

    /**
     * Get template HTML without calling parent _toHtml to avoid executing original problematic code
     *
     * @return string
     */
    private function _getTemplateHtml(): string
    {
        // Use reflection to call grandparent's _toHtml (Template class) to render template
        $reflection = new \ReflectionClass(\Magento\Framework\View\Element\Template::class);
        $method = $reflection->getMethod('_toHtml');
        $method->setAccessible(true);
        return $method->invoke($this);
    }

    /**
     * Assign "fields" values from the ICU data with null checks
     *
     * @param \ResourceBundle $localeData
     */
    private function assignFieldsValues(\ResourceBundle $localeData): void
    {
        /**
         * Fields value in the current position has been added to ICU Data tables
         * starting with ICU library version 51.1.
         * Due to fact that we do not use these variables in templates, we do not initialize them for older versions
         *
         * @see https://github.com/unicode-org/icu/blob/release-50-2/icu4c/source/data/locales/en.txt
         * @see https://github.com/unicode-org/icu/blob/release-51-2/icu4c/source/data/locales/en.txt
         */
        if ($localeData) {
            $fields = $localeData->get('fields');
            if ($fields) {
                $day = $fields->get('day');
                if ($day) {
                    $relative = $day->get('relative');
                    if ($relative) {
                        $today = $relative->get('0');
                        if ($today !== null) {
                            $this->assign('today', $this->encoder->encode($today));
                        }
                    }
                }
                $week = $fields->get('week');
                if ($week) {
                    $dn = $week->get('dn');
                    if ($dn !== null) {
                        $this->assign('week', $this->encoder->encode($dn));
                    }
                }
            }
        }
    }
}


