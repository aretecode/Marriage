<?php

namespace Marriage\Domain\Specification;

class PolyMarriageSpecification implements Specification {
    /**
     * @param  Marriage $marriage 
     */
    public function isSatisfiedBy($marriage) { 
        // if (count $partners) more than 3 (true), else (false)
        return count($marriage->getPartners()) > 3;
    }
}
