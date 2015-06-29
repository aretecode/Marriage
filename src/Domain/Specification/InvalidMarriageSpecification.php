<?php

namespace Marriage\Domain\Specification;

/**
 * could be a ->not(), overkill for now
 */
class InvalidMarriageSpecification implements Specification {
    /**
     * @param  Marriage $marriage 
     */
    public function isSatisfiedBy($marriage) { 
        // {backwards} if valid (invalid=false), else not valid (invalid = true)
        return !$marriage->isValid();
    }
}
