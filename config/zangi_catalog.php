<?php

return [
    'currency' => [
        'site' => 'ZMW',
        'international' => 'USD',
        'usd_to_zmw' => 28,
    ],

    'buyer_types' => ['individual', 'corporate', 'wholesale'],

    'books' => [
        'zangi-flag-of-kindness' => [
            'slug' => 'zangi-flag-of-kindness',
            'title' => 'Zangi: The Flag of Kindness',
            'allowed_buyer_types' => ['individual', 'corporate', 'wholesale'],
            'formats' => [
                'digital' => [
                    'type' => 'digital',
                    'label' => 'Digital',
                    'base_price_usd' => 10.70,
                    'fulfillment' => 'Portal delivery with download-ready status updates',
                ],
                'hardcopy' => [
                    'type' => 'hardcopy',
                    'label' => 'Hardcopy',
                    'base_price_usd' => 12.80,
                    'fulfillment' => 'Physical order tracked through portal milestones',
                ],
            ],
        ],
        'zangi-adventure-activity-book' => [
            'slug' => 'zangi-adventure-activity-book',
            'title' => 'Zangi Adventure Activity Book',
            'allowed_buyer_types' => ['individual', 'corporate', 'wholesale'],
            'formats' => [
                'digital' => [
                    'type' => 'digital',
                    'label' => 'Digital',
                    'base_price_usd' => 9.99,
                    'fulfillment' => 'Portal delivery with download-ready status updates',
                ],
                'hardcopy' => [
                    'type' => 'hardcopy',
                    'label' => 'Hardcopy',
                    'base_price_usd' => 18.99,
                    'fulfillment' => 'Physical order tracked through portal milestones',
                ],
            ],
        ],
    ],

    'events' => [
        'zangi-book-launch-mulungushi-lusaka' => [
            'slug' => 'zangi-book-launch-mulungushi-lusaka',
            'title' => "Zangi's Flag Book Launch",
            'status' => 'upcoming',
            'date_label' => 'May 22, 2026',
            'time_label' => '6:00 PM - 7:30 PM CAT',
            'location_label' => 'Mulungushi International Conference Centre, Lusaka',
            'ticket_types' => [
                'standard' => [
                    'id' => 'standard',
                    'label' => 'Standard',
                    'base_price_usd' => 12.50,
                    'delivery' => 'Digital event pass delivered instantly to the portal after checkout, with a QR placeholder for venue entry.',
                ],
                'vip' => [
                    'id' => 'vip',
                    'label' => 'VIP',
                    'base_price_usd' => 17.8571428571,
                    'delivery' => 'Digital event pass delivered instantly to the portal after checkout, with a QR placeholder for venue entry.',
                ],
            ],
        ],
    ],
];
