<?php

namespace Marriage\Domain\Specification;

/**
 * or/and After, Before, WithDevorcesBetween, etc
 * feel free to upgrade timestamp to DateTime
 */
class MarriageBetweenSpecification implements Specification {
    /**
     * @var DateTime (timestamp)
     */
    protected $start;
    protected $end;

    /**
     * @param DateTime (timestamp) $start
     * @param DateTime (timestamp) $end
     */
    public function __construct($start, $end) {
        $this->start = $start;
        $this->end = $end;
    }        

    /**
     * @param  Marriage $marriage 
     * @return boolean|Marriage Marriage used as boolean. If it matches the conditions.      
     */
    public function isSatisfiedBy($marriage) { 
        $marriages = $marriage->getMarriages();
        foreach ($marriages as $marriageEvent) {
            // if both of these are not true (it is before the start OR after the end)
            if ($marriageEvent->occurredOn < $start || $mariageEvent->occurredOn > $end) {
                return false;
            }
        }

        return $marriage;
    }
}
