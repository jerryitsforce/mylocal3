<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       22/03/2026
 */

namespace Branch8\BlackListKeyWords\Model\AhoCorasick;

class MultiStringMatcher
{

    /** @var string[] The set of keywords to be searched for. */
    protected $searchKeywords = [];

    /** @var int The number of possible states of the string-matching finite state machine. */
    protected $numStates = 1;

    /** @var array Mapping of states to outputs. */
    protected $outputs = [];

    /** @var array Mapping of failure transitions. */
    protected $noTransitions = [];

    /** @var array Mapping of success transitions. */
    protected $yesTransitions = [];

    /**
     * @param $searchKeywords
     * @return MultiStringMatcher
     */
    public function init(array $searchKeywords)
    {
        foreach ($searchKeywords as $keyword) {
            if ($keyword !== '') {
                $this->searchKeywords[$keyword] = strlen($keyword);
            }
        }
        if (!$this->searchKeywords) {
            trigger_error(__METHOD__ . ': The set of search keywords is empty.', E_USER_WARNING);
            // Unreachable 'return' when PHPUnit detects trigger_error
            return; // @codeCoverageIgnore
        }
        $this->computeYesTransitions();
        $this->computeNoTransitions();
        return $this;
    }

    /**
     * Accessor for the search keywords.
     *
     * @return string[] Search keywords.
     */
    public function getKeywords()
    {
        return array_keys($this->searchKeywords);
    }

    /**
     * Map the current state and input character to the next state.
     *
     * @param int $currentState The current state of the string-matching
     *  automaton.
     * @param string $inputChar The character the string-matching
     *  automaton is currently processing.
     * @return int The state the automaton should transition to.
     */
    public function nextState($currentState, $inputChar)
    {
        $initialState = $currentState;
        while (true) {
            $transitions =& $this->yesTransitions[$currentState];
            if (isset($transitions[$inputChar])) {
                $nextState = $transitions[$inputChar];
                // Avoid failure transitions next time.
                if ($currentState !== $initialState) {
                    $this->yesTransitions[$initialState][$inputChar] = $nextState;
                }
                return $nextState;
            }
            if ($currentState === 0) {
                return 0;
            }
            $currentState = $this->noTransitions[$currentState];
        }
        // Unreachable outside 'while'
    } // @codeCoverageIgnore

    /**
     * Locate the search keywords in some text.
     *
     * @param string $text The string to search in.
     * @return array[] An array of matches. Each match is a vector
     *  containing an integer offset and the matched keyword.
     *
     * @par Example:
     * @code
     *   $keywords = new MultiStringMatcher( array( 'ore', 'hell' ) );
     *   $keywords->searchIn( 'She sells sea shells by the sea shore.' );
     *   // result: array( array( 15, 'hell' ), array( 34, 'ore' ) )
     * @endcode
     */
    public function searchIn($text)
    {
        if (!$this->searchKeywords || $text === '') {
            return [];  // fast path
        }

        $state = 0;
        $results = [];
        $length = strlen($text);

        for ($i = 0; $i < $length; $i++) {
            $ch = $text[$i];
            $state = $this->nextState($state, $ch);
            foreach ($this->outputs[$state] as $match) {
                $offset = $i - $this->searchKeywords[$match] + 1;
                $results[] = [$offset, $match];
            }
        }

        return $results;
    }

    /**
     * Get the state transitions which the string-matching automaton
     * shall make as it advances through input text.
     *
     * Constructs a directed tree with a root node which represents the
     * initial state of the string-matching automaton and from which a
     * path exists which spells out each search keyword.
     */
    protected function computeYesTransitions()
    {
        $this->yesTransitions = [[]];
        $this->outputs = [[]];
        foreach ($this->searchKeywords as $keyword => $length) {
            $state = 0;
            for ($i = 0; $i < $length; $i++) {
                $ch = strval($keyword)[$i];
                if (!empty($this->yesTransitions[$state][$ch])) {
                    $state = $this->yesTransitions[$state][$ch];
                } else {
                    $this->yesTransitions[$state][$ch] = $this->numStates;
                    $this->yesTransitions[] = [];
                    $this->outputs[] = [];
                    $state = $this->numStates++;
                }
            }

            $this->outputs[$state][] = $keyword;
        }
    }

    /**
     * Get the state transitions which the string-matching automaton
     * shall make when a partial match proves false.
     */
    protected function computeNoTransitions()
    {
        $queue = [];
        $this->noTransitions = [];

        foreach ($this->yesTransitions[0] as $ch => $toState) {
            $queue[] = $toState;
            $this->noTransitions[$toState] = 0;
        }

        while (true) {
            $fromState = array_shift($queue);
            if ($fromState === null) {
                break;
            }
            foreach ($this->yesTransitions[$fromState] as $ch => $toState) {
                $queue[] = $toState;
                $state = $this->noTransitions[$fromState];

                while ($state !== 0 && empty($this->yesTransitions[$state][$ch])) {
                    $state = $this->noTransitions[$state];
                }

                if (isset($this->yesTransitions[$state][$ch])) {
                    $noState = $this->yesTransitions[$state][$ch];
                } else {
                    $noState = 0;
                }

                $this->noTransitions[$toState] = $noState;
                $this->outputs[$toState] = array_merge(
                    $this->outputs[$toState], $this->outputs[$noState]);
            }
        }
    }
}
