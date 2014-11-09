AceDAO
========================================
#### querying your database DRYly [![Build Status](https://travis-ci.org/karamasoff/acedao.png?branch=master)](https://travis-ci.org/karamasoff/acedao) [![Unicorn approved](https://camo.githubusercontent.com/6d0eb2ffa2340268c1d17e7f8d05bbe9c0404e17/687474703a2f2f696d672e736869656c64732e696f2f62616467652f756e69636f726e2d617070726f7665642d6666363962342e7376673f7374796c653d666c6174)(http://www.voilab.org.)]

----------------------------------------

AceDAO is a query helper for PHP, released under the new BSD license (code
and documentation).

**CAUTION: This package is still not documented and in early development state...**

----------------------------------------

### How does it work ?

AceDAO provide some reusable system to query databases and is build on top of PDO.
Let's query some cars.

	// i want the red cars, with buyer information, sold in the past two month, ordered by price (bigger first)
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

