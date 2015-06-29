<?php

namespace Marriage\Domain;

/**
 * 	Was `Noun`, now `Partner`
 */
class Partner {
    private $partnerId;

    public function partnerId() {
        return $this->partnerId;
    }

    public function __toString() {
        return (string) $this->partnerId;
    }
}
