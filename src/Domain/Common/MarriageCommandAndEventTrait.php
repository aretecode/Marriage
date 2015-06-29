<?php

namespace Marriage\Domain\Common;

/*
 * could also just use $recordedOn, but then can't transfer from legacy as easily
 */
trait MarriageCommandAndEventTrait {
    public $partnerIds;
    public $marriageId;
    public $occurredOn;    

    /** 
     * @param MarriageId $marriageId 
     * @param array<PartnerIds> $partnerIds The partnerIds that are involved in Marriage OR Divorce (or PartnerIds)
     * @param DateTime $occurredOn   
     */
    public function __construct($marriageId, $partnerIds, $occurredOn)
    {    
        $this->marriageId = $marriageId;
        $this->partnerIds = $partnerIds;
        $this->occurredOn = $occurredOn;
    }
}
