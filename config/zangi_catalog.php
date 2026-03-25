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
            'start_date' => '2026-04-03',
            'starts_at' => '2026-04-03T18:00:00+02:00',
            'countdown_starts_at' => '2026-03-25T00:00:00+02:00',
            'timezone' => 'Africa/Lusaka',
            'date_label' => 'May 3, 2026',
            'time_label' => '14:00 PM - 16:30 PM',
            'location_label' => 'NIPA Conference Hall, Lusaka',
            'ticket_sales' => [
                'currency' => 'ZMW',
                'timezone' => 'Africa/Lusaka',
                'sales_start_at' => '2026-04-25T00:00:00+02:00',
                'sales_end_at' => '2026-05-03T23:59:59+02:00',
                'rounds' => [
                    [
                        'key' => 'early_bird',
                        'label' => 'Early Bird',
                        'public_label' => 'Early Bird',
                        'starts_at' => '2026-04-25T00:00:00+02:00',
                        'ends_at' => '2026-04-27T23:59:59+02:00',
                        'standard_price_zmw' => 250,
                    ],
                    [
                        'key' => 'standard',
                        'label' => 'Standard',
                        'public_label' => 'Standard',
                        'starts_at' => '2026-04-28T00:00:00+02:00',
                        'ends_at' => '2026-04-30T23:59:59+02:00',
                        'standard_price_zmw' => 300,
                    ],
                    [
                        'key' => 'late',
                        'label' => 'Late',
                        'public_label' => 'Last Tickets',
                        'starts_at' => '2026-05-01T00:00:00+02:00',
                        'ends_at' => '2026-05-03T23:59:59+02:00',
                        'standard_price_zmw' => 350,
                    ],
                ],
            ],
            'ticket_types' => [
                'standard' => [
                    'id' => 'standard',
                    'label' => 'Standard',
                    'price_strategy' => 'rounds',
                    'delivery' => 'Digital event pass delivered instantly to the portal after checkout, with a QR placeholder for venue entry.',
                ],
                'vip' => [
                    'id' => 'vip',
                    'label' => 'VIP',
                    'price_strategy' => 'fixed',
                    'price_zmw' => 500,
                    'delivery' => 'Digital event pass delivered instantly to the portal after checkout, with a QR placeholder for venue entry.',
                ],
            ],
        ],
    ],
];
