<?php

namespace Marriage\Domain;

use Marriage\Domain\Event\MarriageCreatedEvent;
use Marriage\Domain\Event\DivorcedEvent;
use Marriage\Domain\Event\MarriedEvent;
use Marriage\Domain\Event\MarriageEvent;

/**
 * Assert one 
 *      cannot marry themselves &
 *      cannot be married to nobody
 */
class Marriage extends \Broadway\EventSourcing\EventSourcedAggregateRoot {

    private $marriageId;    
    private $partners = array();

    /**
     * or can these be accessed another way?
     * could 
     *      1) use a better key instead of doing this
     *      2) use an id on the Events
     */
    private $marriages = array();
    private $divorces = array();


    /**
     * Every aggregate root will expose its id.
     *
     * {@inheritDoc}
     */
    public function getAggregateRootId()
    {
        return $this->marriageId;
    }
    /**
     * 
     * if doing optimizations later, can use sets & `return array_keys($this->partners);`
     * @return array<PartnerId|int>
     * 
     */
    public function getPartners()
    {
        return $this->partners;
    }
    public function getMarriages()
    {
        return $this->marriages;
    }

    ///////
    /// if application logic is a little more complex, when divorcing|marrying people, just attempt to add and catch an exception
    /// or, automatically Divorce
    //////
    /**
     * If you can remove 1 person at a time, either this or every time it has to be multiple (or something else haha)
     */
    private $valid = true;
    /**
     *  
     * @return boolean whether the marriage is valid (one person for example, really should be done in a different Class)
     * 
     */
    public function isValid()
    {
        return $this->valid;
    }
    private function validate() {
        // if 1 person, not valid (false). else, `tis valid (true)         
        $this->valid = (1 === count($this->marriages));
    }
    ////////

    /**
     * Factory method to create an Marriage.
     */
    public static function createMarriage($marriageId, $partners, $occurredOn)
    {
        $marriage = new Marriage();
        // After instantiation of the object we apply the "MarriageCreatedEvent".
        $marriage->apply(new MarriageCreatedEvent($marriageId, $partners, $occurredOn));
        return $marriage;
    }

    /**
     * @param PartnerId $partner The partner involved in the Divorce
     * @param DateTime $occurredOn when this happened
     */
    public function divorcePartner($partnerId, $occurredOn) {        
        if (isset($this->partners[$partnerId]))
            throw new RuntimeException('Already Divorced (or was not married in the first place).');

        $this->apply(new DivorcedEvent($partnerId, $occurredOn));
    }

    /**
     * @param PartnerId $partner The partner being married
     * @param DateTime $occurredOn when this happened
     */
    public function marryPartner($partnerId, $occurredOn) {
        if (isset($this->partners[$partnerId]))
            throw new RuntimeException('Already Married.');

        $this->apply(new MarriedEvent($partnerId, $occurredOn));
    }

    /**
     * could also do if the Event->partnerIds was not an array?
     * @return void
     */
    protected function applyMarriedEvent(MarriedEvent $event)
    {
        $this->marriages[] = $event;
        $this->partners = array_merge($this->partners, $event->partnerIds); 
        
        $this->validate();
    }
    protected function applyDivorcedEvent(DivorcedEvent $event)
    {
        $this->divorces[] = $event;
        $this->partners = array_diff($this->partners, $event->partnerIds);

        $this->validate();
    }
    protected function applyMarriageEvent(MarriageEvent $event)
    {
        $this->marriageId = $event->marriageId;
    }
}
