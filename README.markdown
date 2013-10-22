AceDAO
========================================
#### querying your database DRYly [![Build Status](https://travis-ci.org/karamasoff/acedao.png?branch=master)](https://travis-ci.org/karamasoff/acedao)

----------------------------------------

AceDAO is a query helper for PHP, released under the new BSD license (code
and documentation).

**CAUTION: This package is still not documented and in early development state...**

----------------------------------------

### So what ?

AceDAO provide some reusable system to query databases and is build on top of PDO.

	// i want the red cars, with buyer information, sold in the past two month, ordered by price (bigger first)
	$config = array(
		'from' => 'car c',
		'join' => array(
			'buyer b' => array(
				'select' => array('name')
			)
		),
		'where' => array(
			'color' => 'red',
			'selldate' => array(
				'operator' => '>=',
				'value' => '2013-09-01'
			)
		),
		'orderby' => array(
			'price' => 'desc'
		)
	);
	$results = \Acedao\Database::getInstance()->select($config);

The results will be automatically formatted according to the database implicit schema.
You want the cars with the buyer information, you'll get something like this:

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
     </pre>

----------------------------------------

### How can I get it work ?

Read documentation on the different steps to party with it.

1. [Queriable classes][queriable] - First you need to define on which table you want to run queries
2. [Dependancy injection][di] - AceDAO provide a simple yet powerful dependancy injection based on [Pimple][1].
3. [Query configuration][query] - Well, I've my Queriable class, registered in my DI Container. I want to query !!
4. [Bootstrapping][bootstrap] - Ok, and howto bootstrap all that stuffs ?

----------------------------------------


More Information
----------------

All is here :)

[1]: http://pimple.sensiolabs.org/

