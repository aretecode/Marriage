<?php

require_once 'bootstrap.php';

use Marriage\Domain\Repository\MarriageRepository;
use Marriage\Domain\Repository\InMemoryReadRepository;
use Marriage\Domain\Application\MarriageCommandHandler;
use Marriage\Domain\Application\Command\CreateMarriageCommand;
use Marriage\Domain\Application\Command\DivorceCommand;
use Marriage\Domain\Application\Command\MarryCommand;
use Marriage\Domain\Specification\PolyMarriageSpecification;
use Marriage\Domain\Specification\InvalidMarriageSpecification;
use Marriage\Domain\Specification\MarriageBetweenSpecification;

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Unless I ALSO wanted to copy the many other classes here
 * could of course just make bigger class for the EventStore&Repository to query and such...
 *
 * I want this gone
 *
 * TypeHint Params
 */
function transferFromWriteToRead($marriageRepository, $readMarriageRepository) {
   
    // TypeHint: MarriageRepository, InMemoryReadRepository /// was returning ref but obj now
    $eventStoreFromRepository = Closure::bind(function ($marriageRepository) {
        return $eventStore = $marriageRepository->eventStore;
    }, null, $marriageRepository);

    // has to be before $eventStoreData
    $eventStore = $eventStoreFromRepository($marriageRepository);

    // TypeHint: EventStoreInterface
    $eventStoreData = Closure::bind(function &($eventStore) use ($marriageRepository) {
        $events = $eventStore->events;

        $loadedMarriages = array();
        foreach (array_keys($events) as $eventId) {
            $loadedMarriages[$eventId] = $marriageRepository->load($eventId); 
        }

        return $loadedMarriages;
    }, null, $eventStore);

    // has to be after $eventStoreData
    $data =& $eventStoreData($eventStore);
    foreach ($data as $model) {
        $readMarriageRepository->save($model);
    }
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// if you need to listen for something
class MyEventListener implements Broadway\EventHandling\EventListenerInterface
{
    //public function handle(DivorcedEvent $divorcedEvent)
    public function handle(Broadway\Domain\DomainMessage $domainMessage)
    {
        // var_dump($domainMessage);
        echo "Do cool things here!\n";
    }
}


////////////////////////////////////////////////////////////////////////////////

//// Here, all the setting up - 
//// make Store, Bus, 
//// Instantiate & Subscribe your listener(s), make the Repositories, Command handler

// Swap for a real one
$eventStore = new \Broadway\EventStore\InMemoryEventStore();
$eventBus = new \Broadway\EventHandling\SimpleEventBus();

// Subscribe your things here
$eventListener = new MyEventListener();
$eventBus->subscribe($eventListener);

// Setup the command handler
$marriageRepository = MarriageRepository::parent($eventStore, $eventBus);
$commandHandler = new MarriageCommandHandler($marriageRepository);

// Create a command bus and subscribe the command handler at the command bus
$commandBus = new \Broadway\CommandHandling\SimpleCommandBus();
$commandBus->subscribe($commandHandler);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Data for the Command & Marriage
$generator = new \Broadway\UuidGenerator\Rfc4122\Version4Generator();


// @TODO: FLUSH OUT THESE EXAMPLES
$partnerId_1 = $generator->generate();
$partnerId_2 = $generator->generate();
$partnerId_3 = $generator->generate();
$partnerId_4 = $generator->generate();
$partnerId_5 = $generator->generate();
$partnerId_6 = $generator->generate();
$partnerId_7 = $generator->generate();
$marriageId = $generator->generate();

// would think more like `execute`
// Create and dispatch the command!
$occurredOn = time(); // or as new DateTime()

// 2 
$command1 = new CreateMarriageCommand($marriageId, [$partnerId_1, $partnerId_2], $occurredOn);

// 2 different ones
$command2 = new CreateMarriageCommand($marriageId, [$partnerId_3, $partnerId_4], $occurredOn);

// 3 different ones
$command3 = new CreateMarriageCommand($marriageId, [$partnerId_4, $partnerId_5, $partnerId_6], $occurredOn);

// @TODO 3 - merging with existing ones + 2 existing marriages + a new one
$command3 = new CreateMarriageCommand($marriageId, [$partnerId_1, $partnerId_5, $partnerId_6], $occurredOn);

$commandBus->dispatch($command1);
// $commandBus->dispatch($command2);
// $commandBus->dispatch($command3);


// (name, FQCN)
$readMarriageRepository = new InMemoryReadRepository();
transferFromWriteToRead($marriageRepository, $readMarriageRepository);

$polyMarriages = $readMarriageRepository->findSatisfying(new PolyMarriageSpecification());
$invalidMarriages = $readMarriageRepository->findSatisfying(new InvalidMarriageSpecification());
$newMarriages = $readMarriageRepository->findSatisfying(new MarriageBetweenSpecification($start = (time() - 10000), $end = time()));

var_dump($polyMarriages);
var_dump($invalidMarriages);
var_dump($newMarriages);
