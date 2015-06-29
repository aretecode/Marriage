<?php

require_once 'bootstrap.php';

////////////////////////////////////////////////////////////////////////////////
use Broadway\ReadModel\ReadModelInterface;
use Broadway\ReadModel\RepositoryInterface;
use Broadway\ReadModel\TransferableInterface;

trait InMemoryRepositoriesTrait {
    protected $data = array();

    /**
     * {@inheritDoc}
     */
    public function save($model) // ReadModelInterface --- would be, but since in memory... /// getId
    {
        $this->data[(string) $model->getAggregateRootId()] = $model;
    }  
    /**
     * {@inheritDoc}
     */
    public function transferTo( $otherRepository) // TransferableInterface RepositoryInterface
    {
        foreach ($this->data as $model) {
            $otherRepository->save($model);
        }
    }
}

/**
 *
 * https://github.com/qandidate-labs/broadway/blob/master/src/Broadway/ReadModel/InMemory/InMemoryRepository.php
 *
 **
 *
 *  "Please use the underlaying storage directly to do more advanced querying." -- with InMemory?
 **
 *  was just ExtendedInMemoryRepository
 **
 *  http://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
 *  used this... forgot it was a parent property >.>
 **
 *  Rude of them to make $data @private
 *  can't really use findBy to do things like between such and such a date
 *  $readMarriageRepository->findBy();
 *  
 **
 *
 * UGH TOTALLY DID NOT SEE THE TRANSFER TO... WASTE OF 10 MIN
 *
 * took off `implements RepositoryInterface, TransferableInterface`  because really, ::read to an InMemoryWrite model?
 * Additionally, it made TransferTo very difficult
 * 
 */
class InMemoryRepository 
{
    use InMemoryRepositoriesTrait;

    /**
     * {@inheritDoc}
     */
    public function find($id)
    {
        $id = (string) $id;
        if (isset($this->data[$id])) {
            return $this->data[$id];
        }
        return null;
    }
    /**
     * {@inheritDoc}
     */
    public function findBy(array $fields)
    {
        if (! $fields) {
            return array();
        }
        return array_values(array_filter($this->data, function ($model) use ($fields) {
            foreach ($fields as $field => $value) {
                $getter = 'get' . ucfirst($field);
                $modelValue = $model->$getter();
                if (is_array($modelValue) && ! in_array($value, $modelValue)) {
                    return false;
                } elseif (! is_array($modelValue) && $modelValue !== $value) {
                    return false;
                }
            }
            return true;
        }));
    }
    /**
     * {@inheritDoc}
     */
    public function findAll()
    {
        return array_values($this->data);
    }
    /**
    /**
     * {@inheritDoc}
     */
    public function remove($id)
    {
        unset($this->data[(string) $id]);
    }
}

class InMemoryWriteRepository // implements TransferableInterface
{
    use InMemoryRepositoriesTrait;

    /**
     * {@inheritDoc}
     */
    public function remove($id)
    {
        unset($this->data[(string) $id]);
    }
}

/**
 *  https://github.com/qandidate-labs/broadway/blob/master/src/Broadway/ReadModel/InMemory/InMemoryRepository.php
 * 
 *  to use default:
 *  $readRepositoryFactory = new Broadway\ReadModel\InMemory\InMemoryRepositoryFactory();
 *  $readMarriageRepository = $readRepositoryFactory->create('MarriageRepository', 'MarriageRepository');
 *  ||
 *  $readMarriageRepository Broadway\ReadModel\InMemory\InMemoryRepository
 */
class InMemoryReadRepository // implements RepositoryInterface, TransferableInterface 
{
    use InMemoryRepositoriesTrait;

    /**
     * {@inheritDoc}
     */
    public function find($id)
    {
        $id = (string) $id;
        if (isset($this->data[$id])) {
            return $this->data[$id];
        }
        return null;
    }
    /**
     * {@inheritDoc}
     */
    public function findBy(array $fields)
    {
        if (! $fields) {
            return array();
        }
        return array_values(array_filter($this->data, function ($model) use ($fields) {
            foreach ($fields as $field => $value) {
                $getter = 'get' . ucfirst($field);
                $modelValue = $model->$getter();
                if (is_array($modelValue) && ! in_array($value, $modelValue)) {
                    return false;
                } elseif (! is_array($modelValue) && $modelValue !== $value) {
                    return false;
                }
            }
            return true;
        }));
    }
    /**
     * {@inheritDoc}
     */
    public function findAll()
    {
        return array_values($this->data);
    }
    /**
     * 
     * can be 0+, just duplicate to return a single one that is not an array and rename this `findAllSatisfying`
     * @return array<Object|empty>
     * 
     */
    public function findSatisfying(Specification $specification) {
        $data = $this->findAll();
        $result = array();
        foreach ($data as $object) {
            if ($specification->isSatisfiedBy($object)) {
                $result[] = $object;
            }
        }  
        return $result;
    }
}





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





/**
 * Assert one 
 *      cannot marry themselves &
 *      cannot be married to nobody
 */
class Marriage extends Broadway\EventSourcing\EventSourcedAggregateRoot {

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

/**
 * Abstract
 */
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
abstract class MarriageCommand { use MarriageCommandAndEventTrait; }
abstract class MarriageEvent { use MarriageCommandAndEventTrait; }

/*
 * Using  
 */
class MarriageCreatedEvent extends MarriageEvent {}
class CreateMarriageCommand extends MarriageCommand{}

class MarriedEvent extends MarriageEvent {}
class MarryCommand extends MarriageCommand {}

class DivorcedEvent extends MarriageEvent {}
class DivorceCommand extends MarriageCommand {}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// could add a lot of details in Partner and query based on those, or very interesting things in the marriage as mentioned pre-marriage comment
// use a lib (I should put mine up) or whatever this is just for demo
interface Specification {
    /**
     * @return boolean 
     */
    public function isSatisfiedBy($obj);
}

class PolyMarriageSpecification implements Specification {
    /**
     * @param  Marriage $marriage 
     */
    public function isSatisfiedBy($marriage) { 
        // if (count $partners) more than 3 (true), else (false)
        return count($marriage->getPartners()) > 3;
    }
}

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


////////////////////////////////////////////////////////////////////////////////


class MarriageCommandHandler extends Broadway\CommandHandling\CommandHandler
{
    private $repository;
    public function __construct(Broadway\EventSourcing\EventSourcingRepository $repository)
    {
        $this->repository = $repository;
    }
    /**
     * A new invite aggregate root is created and added to the repository.
     */
    protected function handleCreateMarriageCommand(CreateMarriageCommand $command)
    {
        $marriage = Marriage::createMarriage($command->marriageId, $command->partnerIds, $command->occurredOn);
        $this->repository->save($marriage);
    }
    /**
     * An existing invite is loaded from the repository and the accept() method
     * is called.
     */
    protected function handleMarryCommand(MarryCommand $command)
    {
        $marriage = $this->repository->load($command->marriageId);
        $marriage->marry($command->marriageId, $command->partnerIds, $command->occurredOn);
        $this->repository->save($marriage);
    }
    protected function handleDivorceCommand(DivorceCommand $command)
    {
        $marriage = $this->repository->load($command->marriageId);
        $marriage->divorce($command->marriageId, $command->partnerIds, $command->occurredOn);
        $this->repository->save($marriage);
    }
}


////////////////////////////////////////////////////////////////////////////////


class MarriageRepository extends Broadway\EventSourcing\EventSourcingRepository
{
    public function __construct(Broadway\EventStore\EventStoreInterface $eventStore, Broadway\EventHandling\EventBusInterface $eventBus)
    {
        parent::__construct($eventStore, $eventBus, 'Marriage', new Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory);
    }

    /**
     * silly hack to return parent... ~~factory method~~
     * @return Broadway\EventSourcing\EventSourcingRepository
     */
    public static function parent(Broadway\EventStore\EventStoreInterface $eventStore, Broadway\EventHandling\EventBusInterface $eventBus) {
        return new Broadway\EventSourcing\EventSourcingRepository($eventStore, $eventBus, 'Marriage', new Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory);
    }
}


////////////////////////////////////////////////////////////////////////////////


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
$eventStore = new Broadway\EventStore\InMemoryEventStore();
$eventBus = new Broadway\EventHandling\SimpleEventBus();

// Subscribe your things here
$eventListener = new MyEventListener();
$eventBus->subscribe($eventListener);

// Setup the command handler
$marriageRepository = MarriageRepository::parent($eventStore, $eventBus);
$commandHandler = new MarriageCommandHandler($marriageRepository);

// Create a command bus and subscribe the command handler at the command bus
$commandBus = new Broadway\CommandHandling\SimpleCommandBus();
$commandBus->subscribe($commandHandler);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Data for the Command & Marriage
$generator = new Broadway\UuidGenerator\Rfc4122\Version4Generator();


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
