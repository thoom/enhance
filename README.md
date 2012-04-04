Thoom Enhance
=============

Summary
-------

This is a collection of classes that are being developed to enhance using the Silex framework for building
simple websites. It has some utility classes and some abstract database classes that can be extended to enhance the Doctrine
DBAL support that is included with Silex.

Thoom\\Db
---------

The Db classes use an Entity-Manager relationship, where an entity in abstract represents a database row. The manager
is its tie to the database and provides CRUD methods to interact with it.

Understandably, there are many object-oriented database implementations. Doctrine ORM is an obvious alternative as Thoom\Db
also utilizes Doctrine's DBAL to interface with the database. This framework is not meant as a full-featured replacement
but rather a lighter-weight alternative with a different way of managing entities.

### The entity

The entity isn't much more than a data store. The extent of its logic relates to how it silos data into three different
arrays: values, modified, and container.

Each array serves a distinct purpose:

 * __values__: Data in this array is extremely protected, filled in only when the entity is created or in the resetData method.
 The values should represent current database values. This array is compared by the manager when it comes time to generate
 the SQL query to perform an insert or update. This array is the only one stored on serialization!

 * __modified__: This array is filled in whenever data is posted using ArrayAccess or __set methods. Its values are used
 when inserting/updating records in the database.

 * __container__: Any values passed to the entity that are not found in the columns array (defined in the manager) are
 placed in the container array instead. The purpose of the container is to provide a space for additional data to be
 attached that doesn't pollute the table data.

#### Usage

There are several ways to access the data in an entity. Here are a few options:

  * As an object:

        $entity->foo = 'bar';
        echo $entity->foo; //prints "bar"

  * As an array:

        $entity['foo'] = 'bar';
        echo $entity['foo']; //prints "bar"

The entity by default saves data to the modified array if key exists in the values array, otherwise it's saved to the
container's array. This can be overridden very easily by creating get/set methods named after the keys to be overridden.

For example, if we want to make sure that the key _foo_ is made lowercase before it's saved to the modified array, in your
entity class add a setFoo method:

    class BazEntity extends Thoom\Db\EntityAbstract
    {
        protected function setFoo($value)
        {
           $this->modified['foo'] = strtolower($value);
        }
    }

Now if we made the same calls as before:

    $entity['foo'] = 'BaR';
    echo $entity['foo'] // prints "bar" not "BaR"!

Obviously you want to be careful with this functionality, since your input may not match the output!

Using this functionality you could also introduce some faux properties. For instance, say your database stores the user's
name as firstName and lastName. You don't want to have to concat these values all the time. Instead, create a faux property!

    class UserEntity extends Thoom\Db\EntityAbstract
    {
        protected function getFullName()
        {
           return $this->values['firstName'] . ' ' . $this->values['lastName'];
        }
    }

Now use it like any other property (say this user has the first name: _Bruce_ and last name: _Banner_):

    $user = $userManager->read($primaryKey);
    echo $user->fullName; //prints "Bruce Banner"

Note that if you tried to set the full name, it would end up storing the value in the container array unless you also created
a setFullName method:

    protected function setFullName($value)
    {
        $names = explode(' ', $value);

        $this->modified['firstName'] = $names[0];
        $this->modified['lastName'] = $names[1];
    }

Voila!

### The manager

The manager is the entity's interface with the database. The manager behaves as a factory, where it creates new entities and injects
any dependencies. It houses all data about the table it represents, including the table's name, the columns with their default
values and types, the primary key's field name, etc.

The manager uses Dependency Injection to receive its connection to the database.

#### Usage

To use with Silex, I recommend using its DI capability, with the database connection stored in $app['db']:

    $app['mgr.user'] = $app->share(function($app)
    {
        return new MyNamespace\UserManager($app['db']);
    });

Then in your $app _controllers_ you can access the manager using DI:

    $app->get('/user/{primary_key}', function($primary_key) use ($app)
    {
        $user = $app['mgr.user']->read($primary_key);

        return $app['twig']->render('index.html.twig', array('name' => $user['name'], 'email' => $user['email']));
    });

Whenever you send an entity to the Manager to update the database, you will need to refresh the entity if you want it to
reflect the changes made to the database (i.e. via triggers, etc).

Here's an example of creating a new user named Bruce Banner and then refreshing the entity so that the data exists:

    $user = $app['mgr.user']->fresh();
    $user['name'] = 'Bruce Banner';

    $id = $app['mgr.user']->create($user);

    if (!$id){
        //Handle this error condition

    $user['id'] = $id;
    $app['mgr.user']->refresh($user);


Thoom\\Generator
----------------

These classes are static classes that generate various values that you may need in the an application. I frequently use
the RandomString methods to build temporary passwords, and the Uuid class to create unique ids to entities that are put/posted
to a collections url.

### Usage

To create a Uuid:

    $uuid = Thoom\Generator\Uuid::create();
    //outputs something like: ef8dbbaf-681a-4329-b58c-262a6c2c1fb4


To create a random alphanumeric string, lowercase only, 16 characters:

    use Thoom\Generator\RandomString;

    // .... code ... //

    $string = RandomString::alnum(16, RandomString::ALPHANUM_LOWER);
    //outputs something like: asb0z93dg91st73l


To add custom characters (like a dash) to a random string:

    use Thoom\Generator\RandomString;

    // .... code ... //

    $string = RandomString::user(16, array('-'), RandomString::ALPHANUM_LOWER);
    //outputs something like: p2am-53s9xrzb63n
