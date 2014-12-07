---
layout: default
---

[<< BACK - Prerequisites](prereq.html)

1. Queriable classes
========================================

To query a table, you'll have to create a PHP class that implements the Acedao\Queriable interface.

To easily implement that interface Acedao is provided with a Dao *Trait* that implement most of the Queriable interface methods.

Let's take the car example from the first page:

## **Step 1.** Create a class.

```php
<?php
namespace MyApp\Dao;

class Car implements \Acedao\Queriable {
    use \Acedao\Brick\Dao;
    
}

```
Open \Acedao\Brick\Dao.php and check its content to see what default behavior will be given to your new class.


## **Step 2.** Implement missing methods

Now we have our class, quite empty... Let's implement some intelligence inside !

```php
<?php
namespace MyApp\Dao;

class Car implements \Acedao\Queriable {
    use \Acedao\Brick\Dao;
    
    /**
     * List here all fields that will be considered when you will write in this table.
     *
     * @return array
     */
    public function getAllowedFields() {
        return ['id', 'name', 'brand', 'model', 'price', 'selldate', 'buyer_id'];
    }

    /**
     * Defines all the query possibilities of the Queriable object (a table)
     * - join
     * - where
     * - orderby
     *
     * @return array
     */
    public function defineFilters() {
        return [
            'join' => [
                'buyer' => [
                    'on' => [
                        '[this].buyer_id = [buyer].id'
                    ]
                ],
                'car_category' => [
                    'on' => [
                        '[car_category].id = [this].category_id'
                    ]
                ],
                'car_equipment' => [
                    'type' => 'many',
                    'on' => [
                        '[this].id = [car_equipment].car_id'
                    ]
                ],
                'order' => [
                    'on' => [
                        '[this].id = [order].car_id'
                    ]
                ]

            ],
            'where' => [
                'id' => [
                    '[car].id = :id'
                ],
                'category_id' => [
                    '[car].category_id = :categoryId'
                ],
                'color' => [
                    '[this].color = :color'
                ]
            ],
            'orderby' => [
                'date_release' => [
                    '[car].date_release :dir'
                ]
            ]
        ];
    }
}

```

Here are the 2 methods that our Dao *Trait* cannot guess for you.

### getAllowedFields()

The *getAllowedFields()* method defines in a simple array, all the fields you want to consider when saving (or also querying, default behaviour) in your table.

### defineFilters()

The *defineFilters()* method is the place where you will write your SQL... kind of...

This method returns an array that defines all the actions you can perform while querying this table.
It informs us:

- _join_: on which other table you can join
- _where_: what where clause we can invoke on our table
- _orderby_: how we can sort our results according to this table fields.

Let's have a more specific look at each case:

```php
...
return [
    'join' => [
        'buyer' => [
            'on' => [
                '[this].buyer_id = [buyer].id'
            ]
        ],
...
```
This portion of code is how you define a left join clause in acedao.

In (my poor) english, this means: "The car table can join the buyer table and the fields that make the link are buyer_id for the car table and id for the buyer table."

Hint: You could have replace <code>[this].buyer\_id</code> by <code>[car].buyer\_id</code> in this case. It's the same. The use of this is a convenient shortcut.

```php
...
return [
    'where' => [
        'category_id' => [
            '[car].category_id = :categoryId'
        ],
...
```
Here is the way to define a where clause (or a *filter*) when querying your table.

In english: "I want the cars whose category identifier is _the value provided in :categoryId_.

*Note:* :categoryId is simply the native PDO way to provide parameters to a prepared query.  
*Note 2:* Here we use the <code>[car]</code> notation instead of <code>[this]</code>. Same same.

To be continued...