---
layout: default
---


AceDAO 
========================================

#### querying your database DRYly

----------------------------------------

AceDAO is a database query helper for PHP, released under the new BSD license (code
and documentation).

----------------------------------------

### How does it work ?

AceDAO provides some reusable code to query databases and is build on top of PDO.

Let's query some cars.  
I want the red cars, with buyer information, sold in the past two months, ordered by price (bigger first).

```php
// configure the query
$config = [
    'from' => 'car c',
    'join' => [
        'buyer b' => [
            'select' => ['name']
        ]
    ],
    'where' => [
        'color' => 'red',
        'selldate' => [
            'operator' => '>=',
            'value' => '2013-09-01'
        ]
    ],
    'orderby' => [
        'price' => 'desc'
    ]
);

// call the lib
$results = \Acedao\Database::getInstance()->select($config);
```

The results will be automatically formatted according to the database implicit schema.
You want the cars with the buyer information, you'll get something like this:

```php
<pre>Array
(
    [0] => Array
        (
            [id] => 100
            [brand] => Ferrari
            [model] => F40
            [price] => 300000
            [color] => red
            [buyer] => Array
                (
                    [id] => 12
                    [name] => Luke Skywalker
                )

        )

    [1] => Array
        (
            [id] => 13
            [brand] => Lamborghini
            [model] => Murceliago
            [price] => 250000
            [color] => red
            [buyer] => Array
                (
                    [id] => 22
                    [name] => Anakin Skywalker
                )

        )
    )

    [2] => Array
        (
            [id] => 2
            [brand] => Renault
            [model] => 4L
            [price] => 3500
            [color] => violet
            [buyer] => Array
                (
                    [id] => 12
                    [name] => Labrocante Louis
                )

        )
    )
 </pre>
 ```

----------------------------------------

### How can I get it work ?

Read documentation on the different steps to party with it.

0. [Prerequisites](prereq.html) - Before starting, read this.
1. [Queriable classes](queriable.html) - First you need to define on which table you want to run queries
2. [Dependency injection](di.html) - AceDAO provide a simple yet powerful dependency injection based on [Pimple][1].
3. [Query configuration](query.html) - Well, I've my Queriable class, registered in my DI Container. I want to query !!
4. [Bootstrapping](bootstrap.html) - Ok, and howto bootstrap all that stuffs ?

----------------------------------------


More Information
----------------

All is here :)

[1]: http://pimple.sensiolabs.org/
