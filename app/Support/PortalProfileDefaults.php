<?php

namespace App\Support;

class PortalProfileDefaults
{
    public static function forRole(string $role): array
    {
        return match ($role) {
            'corporate' => [
                'headline' => 'Your school and ticket workspace',
                'notes' => [
                    'Track school or organization purchases in one place.',
                    'Use the portal to follow hardcopy order progress and event ticket status.',
                ],
            ],
            'wholesale' => [
                'headline' => 'Your wholesale ordering workspace',
                'notes' => [
                    'Use the portal to review bulk book orders and shipping progress.',
                    'Event ticket management is not enabled for wholesale accounts.',
                ],
            ],
            default => [
                'headline' => 'Your reading and ticket workspace',
                'notes' => [
                    'Use the portal to track orders, digital access, and event tickets.',
                    'Your latest book purchases and ticket passes appear here after checkout.',
                ],
            ],
        };
    }
}
