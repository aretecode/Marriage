## Event Sourced MarriagES
MarriagES is an example event sourced system for dealing with (2 or more) partners getting married with the future in mind.

After reading [Gay marriage: the database engineering perspective:](http://qntm.org/gay), I set a timer for 2 hours (had to set th) and made an example approach to the problem.


// , 
// use a lib (I should put mine up) or whatever this is just for demo

## Todo 

### .Partner
can EventSource as well, use standard types, extend with *noun* details & could be queried

### .Marriage
easy to extend to add additional data such as ```[location] [guests > rank (best, normal, parent)] [preacher] [vows] [comments] [engagement]```
could abstract to make ```Marry``` the same as ```Create Marriage```

### Naming & Syntax
could change all ```PartnerIds``` to ```Partners```
could format it according to the [PSR-2 standard](http://www.php-fig.org/psr/psr-2/)
::transferFromWriteToRead is a real lame/shamefull hack, suggestions on how to change are welcome


### Libraries & Implementation
would love to see it using Doctrine, Aura.SQL & using SQL or Mongo or anything else.
cool to see a little html & js page using it
could have Divorce|MarryAll application usage

### Tests
If people show interest in this, I'll write tests

###Examples
Flush out the examples in the index for more uses, pre-tests
