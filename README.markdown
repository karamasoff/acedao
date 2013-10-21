AceDAO
========================================
#### querying your database DRYly

----------------------------------------

[![Build Status](https://travis-ci.org/karamasoff/acedao.png?branch=master)](https://travis-ci.org/karamasoff/acedao)

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
You get the cars with the buyer information, you'll get something like:

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


AceDAO is a query helper for PHP, released under the new BSD license (code
and documentation).

**CAUTION: This package is still not documented and in early development state...**

More Information
----------------

Read the [documentation][1] for more information (soon).

[1]: http://acedao.voilab.org/documentation

