<?php
return [
    'acedao.tables' => [

        'buyer' => [
            'table' => 'buyer',
            'alias' => 'b',
            'fields' => ['id', 'firstname', 'lastname'],
            'filters' => [
                'join' => array(
                    'car' => array(
                        'on' => array(
                            '[car].buyer_id = [buyer].id'
                        )
                    )

                ),
                'where' => array(
                    'id' => array(
                        '[buyer].id = :id'
                    )
                ),
                'orderby' => array(
                    'name' => array(
                        '[buyer].lastname :dir',
                        '[buyer].firstname :dir'
                    )
                )
            ]
        ],

        'car' => [
            'table' => 'car',
            'alias' => 'c',
            'fields' => ['id', 'name', 'brand', 'model', 'price', 'selldate', 'buyer_id'],
            'filters' => [
                'join' => array(
                    'buyer' => array(
                        'on' => array(
                            '[this].buyer_id = [buyer].id'
                        )
                    ),
                    'car_category' => array(
                        'on' => array(
                            '[car_category].id = [this].category_id'
                        )
                    ),
                    'car_equipment' => array(
                        'type' => 'many',
                        'on' => array(
                            '[this].id = [car_equipment].car_id'
                        )
                    ),
                    'order' => array(
                        'on' => array(
                            '[this].id = [order].car_id'
                        )
                    )

                ),
                'where' => array(
                    'id' => array(
                        '[car].id = :id'
                    ),
                    'category_id' => array(
                        '[car].category_id = :categoryId'
                    ),
                    'color' => array(
                        '[this].color = :color'
                    )
                ),
                'orderby' => array(
                    'date_release' => array(
                        '[car].date_release :dir'
                    )
                )
            ]
        ],

        'car_category' => [
            'table' => 'car_category',
            'alias' => 'cc',
            'fields' => ['id', 'name', 'description', 'enabled'],
            'filters' => [
                'join' => array(
                    'car' => array(
                        'on' => array(
                            '[car].category_id = [car_category].id'
                        )
                    )
                ),
                'where' => array(
                    'enabled' => array(
                        '[car_category].enabled = 1'
                    )
                ),
                'orderby' => array(
                    'name' => array(
                        '[car_category].name :dir'
                    )
                )
            ]
        ],

        'car_equipment' => [
            'table' => 'car_equipment',
            'alias' => 'ceq',
            'fields' => ['id', 'car_id', 'equipment_id'],
            'filters' => [
                'join' => array(
                    'car' => array(
                        'on' => array(
                            '[car].id = [car_equipment].car_id'
                        )
                    ),
                    'equipment' => array(
                        'on' => array(
                            '[equipment].id = [car_equipment].equipment_id'
                        )
                    )
                ),
                'where' => array(),
                'orderby' => array()
            ]
        ],

        'equipment' => [
            'table' => 'equipment',
            'alias' => 'eq',
            'fields' => ['id', 'name', 'description', 'price', 'enabled'],
            'filters' => [
                'join' => array(
                    'car_equipment' => array(
                        'on' => array(
                            '[car_equipment].equipment_id = [equipment].id'
                        )
                    )
                ),
                'where' => array(
                    'enabled' => array(
                        '[equipment].enabled = true'
                    ),
                    'price_between' => array(
                        '[this].price >= :from',
                        '[this].price <= :to'
                    )
                ),
                'orderby' => array(
                    'price' => array(
                        '[equipment].price :dir'
                    )
                )
            ]
        ],

        'order' => [
            'table' => 'order',
            'alias' => 'o',
            'escapeTablename' => true,
            'fields' => ['id', 'date', 'amount'],
            'filters' => [
                'join' => array(
                    'car' => array(
                        'on' => array(
                            '[car].id = [this].car_id'
                        )
                    )
                ),
                'where' => array(
                    'id' => array(
                        '[this].id = :id'
                    )
                ),
                'orderby' => array(
                    'date' => array(
                        '[this].date :dir'
                    )
                )
            ]
        ]
    ]

];