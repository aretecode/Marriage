<?php

namespace Marriage\Domain\Specification;

// @TODO: rename to SpecificationInterface
interface Specification {
    /**
     * @return boolean 
     */
    public function isSatisfiedBy($object);
}
