<?php

namespace Marriage\Domain\Repository;

class MarriageRepository extends \Broadway\EventSourcing\EventSourcingRepository
{
    public function __construct(\Broadway\EventStore\EventStoreInterface $eventStore, \Broadway\EventHandling\EventBusInterface $eventBus)
    {
        parent::__construct($eventStore, $eventBus, 'Marriage\Domain\Marriage', new \Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory);
    }

    /**
     * silly hack to return parent... ~~factory method~~
     * @return Broadway\EventSourcing\EventSourcingRepository
     */
    public static function parent(\Broadway\EventStore\EventStoreInterface $eventStore, \Broadway\EventHandling\EventBusInterface $eventBus) {
        return new \Broadway\EventSourcing\EventSourcingRepository($eventStore, $eventBus, 'Marriage\Domain\Marriage', new \Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory);
    }
}
