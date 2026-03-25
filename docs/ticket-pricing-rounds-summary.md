# Ticket Pricing Rounds System - Implementation Summary

## Overview
The ticket sales system now supports **3 pricing rounds** with dynamic pricing based on the current date.

## Event Schedule
**Event:** Zangi's Flag Book Launch  
**Date:** May 3, 2026  
**Venue:** NIPA Conference Hall, Lusaka  
**Sales Period:** April 25 - May 3, 2026

## Pricing Rounds

| Round | Period | Standard Ticket Price | VIP Ticket Price |
|-------|--------|----------------------|------------------|
| **Early Bird** | April 25-27, 2026 | 250 ZMW | 500 ZMW (fixed) |
| **Standard** | April 28-30, 2026 | 300 ZMW | 500 ZMW (fixed) |
| **Late (Last Tickets)** | May 1-3, 2026 | 350 ZMW | 500 ZMW (fixed) |

## Changes Made

### Backend (PHP)

1. **`config/zangi_catalog.php`**
   - Updated sales dates: April 25 - May 3, 2026
   - Configured 3 pricing rounds with correct dates and prices
   - Event date changed to May 3, 2026

2. **`app/Services/CatalogService.php`**
   - Made error message dynamic (shows actual sales start date)
   - Resolves current pricing round based on current date/time
   - Validates sales window before allowing purchases

3. **`app/Services/Admin/AdminDataService.php`**
   - Added `eventsWithTicketTypes()` method
   - Added `getCurrentPricingRound()` helper method
   - Returns real-time pricing data from backend config

4. **`app/Http/Controllers/Api/V1/Admin/EventController.php`** (NEW)
   - New controller to expose event data with ticket pricing
   - Returns current round prices dynamically

5. **`routes/api.php`**
   - Added new route: `GET /api/v1/admin/events`
   - Requires admin authentication

6. **`tests/Feature/Api/V1/EventTicketPricingRoundTest.php`**
   - Updated all test dates to match new schedule
   - All 4 tests passing ✓

### Frontend (JavaScript/React)

1. **`resources/js/admin/api/adminApiClient.js`**
   - Added `fetchAdminEvents()` function
   - Fetches real pricing data from backend API

2. **`resources/js/admin/mocks/AdminMockDataProvider.jsx`**
   - Now fetches events from API on load
   - Falls back to mock data if API fails
   - Uses real pricing from backend when available

3. **`resources/js/admin/mocks/adminMockData.js`**
   - Updated mock data with correct dates (May 3, 2026)
   - Updated Standard ticket price to 250 ZMW (Early Bird)
   - Added `priceStrategy`, `currentRoundKey`, `currentRoundLabel` fields

## How It Works

### For Online Sales
1. User selects event and ticket type
2. Backend `CatalogService::resolveEventTicketOffer()` determines current round
3. Price is calculated based on current round
4. System validates sales window (blocks before April 25 or after May 3)

### For Admin Manual Sales
1. Admin opens manual sales page
2. Frontend calls `GET /api/v1/admin/events`
3. Backend returns events with **current round prices**
4. If API fails, falls back to mock data
5. Price displayed matches current pricing round

### Pricing Round Logic
```php
// Current round is determined by:
$current = now()->timezone('Africa/Lusaka');

foreach ($rounds as $round) {
    if ($current->betweenIncluded($startsAt, $endsAt)) {
        return $round; // Use this round's price
    }
}
```

## API Response Example

```json
GET /api/v1/admin/events
{
  "data": [
    {
      "slug": "zangi-book-launch-mulungushi-lusaka",
      "title": "Zangi's Flag Book Launch",
      "dateLabel": "May 3, 2026",
      "timeLabel": "14:00 PM - 16:30 PM",
      "venue": "NIPA Conference Hall, Lusaka",
      "ticketTypes": [
        {
          "id": "standard",
          "label": "Standard",
          "price": 250,
          "priceStrategy": "rounds",
          "currentRoundKey": "early_bird",
          "currentRoundLabel": "Early Bird"
        },
        {
          "id": "vip",
          "label": "VIP",
          "price": 500,
          "priceStrategy": "fixed"
        }
      ]
    }
  ]
}
```

## Testing

All tests passing:
```
✓ standard ticket round prices switch across boundary dates
✓ vip ticket price stays fixed while round metadata tracks sale window
✓ event ticket sales are blocked before opening and after closing
✓ event ticket checkout rejects usd and sends confirmation after paid verification
```

## Notes

- **VIP tickets** maintain a fixed price (500 ZMW) across all rounds
- **Standard tickets** use dynamic pricing based on current round
- System automatically switches rounds at midnight (Africa/Lusaka timezone)
- Error messages show actual dates dynamically
- Frontend gracefully degrades to mock data if API is unavailable
