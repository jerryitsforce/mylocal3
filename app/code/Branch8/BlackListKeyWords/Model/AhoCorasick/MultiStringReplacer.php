<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       22/03/2026
 */

namespace Branch8\BlackListKeyWords\Model\AhoCorasick;

/**
 * Search and replace functionality.
 */
class MultiStringReplacer extends MultiStringMatcher
{

    /** @var array Mapping of states to outputs. */
    protected $replacePairs = [];

    /**
     * @param array $replacePairs
     * @return void
     */
    public function init(array $replacePairs)
    {
        foreach ($replacePairs as $keyword => $replacement) {
            if ($keyword !== '') {
                $this->replacePairs[$keyword] = $replacement;
            }
        }
        parent::init(array_keys($this->replacePairs));
    }
    /**
     * Search and replace a set of keywords in some text.
     *
     * @param string $text The string to search in.
     * @return string The input text with replacements.
     *
     * @par Example:
     * @code
     *   $replacer = new MultiStringReplacer( array( 'csh' => 'sea shells' ) );
     *   $replacer->searchAndReplace( 'She sells csh by the sea shore.' );
     *   // result: 'She sells sea shells by the sea shore.'
     * @endcode
     */
    public function searchAndReplace($text)
    {
        $state = 0;
        $length = strlen($text);
        $matches = [];
        for ($i = 0; $i < $length; $i++) {
            $ch = $text[$i];
            $state = $this->nextState($state, $ch);
            foreach ($this->outputs[$state] as $match) {
                $offset = $i - $this->searchKeywords[$match] + 1;
                $matches[$offset] = $match;
            }
        }
        ksort($matches);

        $buf = '';
        $lastInsert = 0;
        foreach ($matches as $offset => $match) {
            if ($offset >= $lastInsert) {
                $buf .= substr($text, $lastInsert, $offset - $lastInsert);
                $buf .= $this->replacePairs[$match];
                $lastInsert = $offset + $this->searchKeywords[$match];
            }
        }
        $buf .= substr($text, $lastInsert);

        return $buf;
    }
}
