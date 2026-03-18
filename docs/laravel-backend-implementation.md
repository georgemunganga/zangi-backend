# Zangi Laravel Backend Implementation Blueprint

## Purpose

This backend owns only the transactional layer for Zangi:

- portal auth and OTP
- book checkout
- event ticket checkout
- payment intents and payment verification
- dashboard data
- contact messages
- newsletter subscriptions

The public catalog stays static in the frontend. Laravel must not expose CRUD or public read APIs for books, events, or help content.

## Core decisions

- Framework: Laravel 12
- Auth: bearer tokens with Sanctum
- OTP: six-digit email OTP, server-generated, 10-minute expiry
- Catalog validation: read-only mirror in `config/zangi_catalog.php`
- Currency rules:
  - ZMW for Zambia
  - USD for international
- Payment rules:
  - Zambia digital books: mobile money, card
  - Zambia hardcopy books: mobile money, card, cash on delivery
  - international books: card
  - Zambia event tickets: mobile money, card
  - international event tickets: card

## Models and tables

- `PortalUser`
  - table: `portal_users`
  - fields: `id`, `role`, `name`, `email`, `phone`, `organization_name`, `headline`, `notes`, `verified_at`, timestamps
- `PortalOtpChallenge`
  - table: `portal_otp_challenges`
  - fields: `id`, `email`, `role`, `code_hash`, `expires_at`, `attempts`, `consumed_at`, timestamps
- `Order`
  - table: `orders`
  - fields: `id`, `reference`, `portal_user_id`, `buyer_type`, `email`, `phone`, `organization_name`, `product_slug`, `product_title`, `format`, `quantity`, `currency`, `unit_price`, `total`, `status`, `timeline`, `current_step`, `payment_status`, `payment_method`, `download_ready`, `download_path`, timestamps
- `TicketPurchase`
  - table: `ticket_purchases`
  - fields: `id`, `reference`, `portal_user_id`, `buyer_type`, `email`, `phone`, `organization_name`, `event_slug`, `event_title`, `date_label`, `time_label`, `location_label`, `ticket_type_id`, `ticket_type_label`, `ticket_holder_name`, `buyer_name`, `quantity`, `currency`, `unit_price`, `total`, `status`, `ticket_code`, `qr_path`, `pass_path`, timestamps
- `PaymentIntent`
  - table: `payment_intents`
  - fields: `id`, `reference`, `purchase_type`, `purchase_id`, `buyer_type`, `email`, `currency`, `amount`, `payment_method`, `status`, `lenco_payload`, `lenco_response`, `return_path`, `verified_at`, timestamps
- `RefreshToken`
  - table: `refresh_tokens`
  - fields: `id`, `portal_user_id`, `token_hash`, `expires_at`, `revoked_at`, timestamps
- `ContactMessage`
  - table: `contact_messages`
  - fields: `id`, `name`, `email`, `message`, `status`, timestamps
- `NewsletterSubscriber`
  - table: `newsletter_subscribers`
  - fields: `id`, `email`, `subscribed_at`, timestamps

## Read-only catalog mirror

Create `config/zangi_catalog.php` with:

- books keyed by slug
- each book containing title, allowed formats, base USD prices, and allowed buyer roles
- events keyed by slug
- each event containing title, status, and ticket types keyed by ticket ID

This config is the server-side validation source for checkout. The frontend display data may stay in `mock.js`, but the backend never trusts frontend totals.

## Endpoint contract

### Auth

- `POST /api/v1/auth/request-otp`
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/verify-otp`
- `POST /api/v1/auth/refresh`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me`

### Checkout and payments

- `POST /api/v1/checkout/book-orders/online-intent`
- `POST /api/v1/checkout/book-orders/cod`
- `POST /api/v1/checkout/event-tickets/online-intent`
- `POST /api/v1/payments/lenco/verify`
- `POST /api/v1/payments/lenco/webhook`

### Portal

- `GET /api/v1/portal/overview`
- `GET /api/v1/portal/orders`
- `GET /api/v1/portal/orders/{order}`
- `GET /api/v1/portal/orders/{order}/download`
- `GET /api/v1/portal/tickets`
- `GET /api/v1/portal/tickets/{ticket}`
- `GET /api/v1/portal/tickets/{ticket}/pass`

### Website actions

- `POST /api/v1/contact/messages`
- `POST /api/v1/newsletter/subscribers`

## Workflow notes

### Portal auth

1. Frontend requests OTP by email.
2. Backend finds or creates the portal user depending on endpoint.
3. Backend emails a 6-digit code.
4. Frontend verifies code.
5. Backend marks user verified and returns access + refresh tokens.

### Book checkout

1. Frontend submits product slug, format, quantity, buyer details, currency, payment method.
2. Backend validates against `zangi_catalog` and computes totals.
3. If COD:
   - create order immediately
   - mark payment status `Pending on Delivery`
4. If online:
   - create pending order
   - create payment intent
   - return Lenco widget payload
5. After Lenco success, backend verify/webhook finalizes the order.

### Event checkout

1. Frontend submits event slug, ticket type, quantity, buyer details, currency, payment method.
2. Backend validates against read-only event config.
3. Backend creates pending ticket purchase and payment intent.
4. Lenco verification finalizes the ticket purchase and generates ticket code/pass data.

### Dashboard

1. Frontend reads `auth/me` for session user.
2. Frontend fetches overview, orders, and tickets from portal routes.
3. Backend scopes every order and ticket by authenticated user email + role.

## Files and deliverables to implement

- API routes file under `routes/api.php`
- request validation classes for auth, checkout, contact, newsletter
- controllers for auth, checkout, payments, portal, contact, newsletter
- services for OTP, catalog validation, currency rules, Lenco integration, token refresh
- migrations for all transactional tables
- mail class for OTP delivery
- config file for the read-only catalog
- storage-backed signed download/pass responses

## Default environment setup

- Database: use the available `zangi` database
- Mail: real mail transport for OTP delivery in non-local environments
- Queue: database queue for OTP email and payment follow-up jobs
- Filesystem: local/private disk for digital books and ticket passes
- Lenco env:
  - `LENCO_PUBLIC_KEY`
  - `LENCO_SECRET_KEY`
  - `LENCO_WEBHOOK_SECRET`
  - `LENCO_API_BASE_URL`
  - `LENCO_REDIRECT_BASE_URL`
- webhook route to register in Lenco:
  - `/api/v1/payments/lenco/webhook`

## Current implementation status

The Laravel scaffold has now been converted into a transactional backend foundation with:

- `routes/api.php` wiring for auth, checkout, payments, portal, contact, and newsletter
- read-only catalog validation in `config/zangi_catalog.php`
- transactional migrations for:
  - `portal_users`
  - `portal_otp_challenges`
  - `orders`
  - `ticket_purchases`
  - `payment_intents`
  - `refresh_tokens`
  - `contact_messages`
  - `newsletter_subscribers`
- Sanctum personal access tokens for bearer auth
- OTP mail generation with `PortalOtpMail`
- controller/service structure for:
  - portal auth
  - book checkout
  - event ticket checkout
  - Lenco intent/verify/webhook endpoints
  - portal overview, orders, tickets
  - contact and newsletter capture

## Frontend compatibility notes

The backend supports both:

- canonical versioned routes under `/api/v1/...`
- compatibility aliases for the current frontend payment calls:
  - `POST /api/payments/lenco/intent`
  - `POST /api/payments/lenco/verify`

This lets the current React checkout keep working against Laravel once the frontend API base URL is pointed to this backend.

## MySQL `zangi` setup

Update `.env` to use the local MySQL `zangi` database:

```env
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zangi
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply@zangi.local"
MAIL_FROM_NAME="${APP_NAME}"

LENCO_PUBLIC_KEY=your_lenco_public_key
LENCO_SECRET_KEY=your_lenco_secret_key
LENCO_WEBHOOK_SECRET=your_lenco_webhook_secret
LENCO_API_BASE_URL=https://api.lenco.co/access/v2
LENCO_REDIRECT_BASE_URL=http://localhost:3000
```

Then run:

```bash
php artisan migrate --force
php artisan serve
```

Webhook URL note:

- local route path: `http://localhost:8000/api/v1/payments/lenco/webhook`
- real Lenco dashboard webhook must use a public HTTPS URL, for example:
  - `https://your-domain.com/api/v1/payments/lenco/webhook`

## Remaining integration work

- replace frontend localStorage auth/orders/tickets with these Laravel APIs
- connect real Lenco credentials and confirm webhook signature/header format against the merchant docs
- add real digital book files and ticket pass generation so portal download/pass endpoints return actual assets
- switch contact/newsletter frontend forms to the new API routes
