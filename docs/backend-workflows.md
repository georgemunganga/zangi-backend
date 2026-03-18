# Zangi Backend Workflows

## 1. Portal signup and login

### Signup
1. Frontend calls `POST /api/v1/auth/register`
2. Laravel creates `portal_users` row with role-specific defaults
3. Laravel creates `portal_otp_challenges` row
4. Laravel emails the OTP
5. Frontend calls `POST /api/v1/auth/verify-otp`
6. Laravel verifies the code, marks the user verified, and returns:
   - Sanctum access token
   - refresh token
   - user payload

### Login
1. Frontend calls `POST /api/v1/auth/request-otp`
2. Laravel looks up the existing `portal_users` row
3. Laravel creates a fresh OTP challenge and emails it
4. Frontend calls `POST /api/v1/auth/verify-otp`
5. Laravel returns bearer auth tokens and user payload

## 2. Book checkout

### Online payment
1. Frontend calls `POST /api/payments/lenco/intent` or `POST /api/v1/checkout/book-orders/online-intent`
2. Laravel validates:
   - book slug
   - format
   - buyer type
   - allowed payment method
   - server-side price using `config/zangi_catalog.php`
3. Laravel creates:
   - pending `orders` row
   - pending `payment_intents` row
4. Laravel returns Lenco widget config
5. Frontend opens Lenco widget
6. Frontend calls `POST /api/payments/lenco/verify`
7. Laravel verifies against Lenco and finalizes the order if paid

### Payment on delivery
1. Frontend calls `POST /api/v1/checkout/book-orders/cod`
2. Laravel validates that:
   - the book format is `hardcopy`
   - currency is `ZMW`
   - COD is allowed
3. Laravel creates the order immediately with `Pending on Delivery`

## 3. Event ticket checkout

1. Frontend calls `POST /api/payments/lenco/intent` or `POST /api/v1/checkout/event-tickets/online-intent`
2. Laravel validates:
   - event slug
   - ticket type
   - buyer type
   - allowed payment method
   - server-side total
3. Laravel creates:
   - pending `ticket_purchases` row
   - pending `payment_intents` row
4. Frontend opens Lenco widget
5. Frontend calls `POST /api/payments/lenco/verify`
6. Laravel finalizes the ticket purchase and stores a ticket code when paid

## 4. Portal dashboard

### Overview
- `GET /api/v1/portal/overview`
- returns:
  - latest purchase reference
  - metrics
  - recent activity
  - notes
  - ticket support flag

### Orders
- `GET /api/v1/portal/orders?format=all|digital|hardcopy`
- `GET /api/v1/portal/orders/{order}`
- `GET /api/v1/portal/orders/{order}/download`

### Tickets
- `GET /api/v1/portal/tickets`
- `GET /api/v1/portal/tickets/{ticketPurchase}`
- `GET /api/v1/portal/tickets/{ticketPurchase}/pass`

## 5. Non-checkout website actions

### Contact form
- `POST /api/v1/contact/messages`
- stores inbound website support/contact messages

### Newsletter
- `POST /api/v1/newsletter/subscribers`
- stores newsletter subscriptions idempotently by email
