# Zangi Admin Dashboard UI-First Plan

## Summary

- Build the admin dashboard as a separate `/admin` route group in the existing React frontend, not in Blade or Inertia.
- Use a UI-first phased approach: ship the admin shell, pages, and reusable components against mock data first, then connect Laravel admin APIs in phases.
- Keep admin auth separate from the customer portal. V1 admin auth is email + password backed by Sanctum tokens and an `admin_users` table.
- Limit V1 to operations: overview, tickets, validation, manual sales, orders, customers, contact messages, payments, reports summary, and basic settings.
- Do not build CRUD for books, products, or events in V1. Books and events remain hardcoded and are used only for labels, filters, and sales validation.

## Current Repo Reality

- This backend repo currently contains Laravel transactional APIs and only minimal Vite assets under `resources/js`.
- The React admin app is not in this repo today.
- The plan still stands: the admin UI should live in the existing React frontend codebase, while this Laravel backend owns the admin API, auth, and business rules.
- If the React app is later moved into this repo, the route/component structure below still applies.

## Product Decision

### Why UI-first

- It is the fastest way to get stakeholders something usable and reviewable.
- It forces the admin information architecture, states, and actions to be clear before backend work expands.
- It lets us reuse one design system instead of building page-specific widgets.
- It reduces churn: backend contracts can be shaped to real UI needs instead of guessed from a feature list.

### Why React admin and not Blade/Inertia

- The target experience is operational and state-heavy: tables, filters, drawers, dialogs, validation flows, and responsive navigation.
- A dedicated React admin area will move faster once the shell and primitive set exist.
- Blade/Inertia would split the frontend patterns and slow down reuse.

## V1 Scope

### In scope

- Admin login/logout and password change
- Admin shell and navigation
- Overview dashboard
- Tickets management
- Ticket validation
- Manual sales
- Orders
- Customers
- Contact messages
- Payments
- Reports summary with export triggers
- Basic settings

### Out of scope

- Event CRUD
- Book/product CRUD
- Inventory management beyond basic hardcoded labels
- Full RBAC or multi-role permissions
- Seat allocation
- Waitlists
- Bundles
- Advanced analytics builder
- Offline validation as a guaranteed mode

## UX Direction

- Minimal and data-first
- Quiet neutral surfaces with restrained Zangi accent usage
- Compact spacing and strong typography
- Table-first on desktop, card-first on mobile where needed
- Shared loading, empty, success, and error patterns across all admin pages
- Very little decorative chrome

## Frontend Structure

### Admin routes

- `/admin/login`
- `/admin`
- `/admin/overview`
- `/admin/tickets`
- `/admin/tickets/validation`
- `/admin/manual-sales`
- `/admin/orders`
- `/admin/customers`
- `/admin/contact`
- `/admin/payments`
- `/admin/reports`
- `/admin/settings`

### Suggested frontend folders

- `src/routes/admin/*`
- `src/components/admin/*`
- `src/features/admin/*`
- `src/features/admin/api/*`
- `src/features/admin/mocks/*`

### Reusable admin component layer

- `AdminShell`
- `AdminSidebar`
- `AdminHeader`
- `AdminPageHeader`
- `AdminStatCard`
- `AdminSectionCard`
- `AdminFilterBar`
- `AdminDataTable`
- `AdminStatusBadge`
- `AdminDetailDrawer`
- `AdminActionDialog`
- `AdminEmptyState`
- `AdminActivityList`
- `AdminKpiGrid`

### Layout rules

- Dedicated admin shell, separate from public and portal layouts
- Compact left rail on desktop
- Slim top bar with page title, search slot, and admin menu
- Mobile bottom nav or compact icon rail for the highest-traffic sections
- "More" sheet for lower-frequency sections on mobile

## UI-First Delivery Phases

### Phase 1: Admin UI Foundation

### Goal

Stand up the admin shell and reusable design system before any real backend integration.

### Deliverables

- Admin route group and route protection wrapper
- `AdminShell`, `AdminSidebar`, `AdminHeader`, `AdminPageHeader`
- shared table, filter, drawer, dialog, badge, empty, and skeleton patterns
- base page shells for all V1 sections
- mock fixtures and adapters for all admin entities

### Data strategy

- Use stable mock JSON fixtures that mirror the future API contracts
- Keep a single adapter layer so the UI swaps from mock data to real APIs without page rewrites

### Exit criteria

- Every admin route renders
- Desktop and mobile nav work
- Shared page states exist for loading, empty, and error
- No page depends on backend availability

### Phase 2: Workflow-Complete UI on Mock Data

### Goal

Make the UI operational from a product and UX perspective before wiring the backend.

### Deliverables by page

- Overview: KPI strip, recent orders, recent contact messages, upcoming events, action queue, one small trend visual at most
- Tickets: search, filters, status badges, drawer, row actions
- Ticket validation: fast manual code entry, validation result states, future QR scan slot
- Manual sales: POS-style issue flow with paid, unpaid, and reserved paths
- Orders: unified order list with drawer and status actions
- Customers: searchable list with profile and history panel
- Contact: inbox list, status transitions, reply composer shell
- Payments: payment list and reconciliation actions
- Reports: summary cards and export triggers
- Settings: admin profile and change password screens

### Exit criteria

- The admin can be fully demoed with realistic data
- Every page has a clear primary action
- The design system is reused instead of duplicated

### Phase 3: Backend Admin Auth and Read APIs

### Goal

Bring the backend up to the minimum level needed to support protected admin access and read-only operational views.

### Backend deliverables in this Laravel repo

- `admin_users` migration and model
- admin auth controller namespace under `App\\Http\\Controllers\\Api\\V1\\Admin`
- request validation classes for admin auth and admin filters
- Sanctum-backed admin login, logout, me, and change-password endpoints
- overview, tickets, orders, customers, contact messages, payments, and reports summary read endpoints

### Exit criteria

- Admin login works independently of portal auth
- Protected admin routes resolve against real backend data
- UI no longer depends on mock data for read paths

### Phase 4: Backend Mutations and Exports

### Goal

Wire the actions that make the dashboard operational.

### Deliverables

- ticket validate, mark-used, void, reissue, resend, download, export
- manual issue flow
- order status, confirm-payment, refund, cancel, invoice
- contact message reply and status update
- payment reconcile and refund
- report export

### Exit criteria

- Core admin actions persist correctly
- UI confirms or rejects sensitive actions clearly
- Cross-screen data stays consistent after mutations

### Phase 5: Hardening and Release Readiness

### Goal

Stabilize the admin experience for real operational use.

### Deliverables

- responsive polish
- audit/log placeholder
- sensitive action confirmations
- empty-state and failure-state consistency review
- end-to-end testing for critical flows

## Page Requirements

### Overview

- Revenue, tickets sold, manual tickets, orders, pending payments, failed payments
- Recent orders
- Recent contact messages
- Upcoming events from hardcoded event config
- Pending operational actions queue
- No heavy chart wall

### Tickets

- Search by ticket code, name, email, phone
- Filter by event, ticket type, status, payment status, source
- Detail drawer with lifecycle, buyer, payment, delivery, and notes
- Actions: validate, mark used, void, reissue, resend, download

### Ticket Validation

- Manual code entry is the guaranteed V1 path
- QR/camera scan UI can exist as a placeholder slot
- Result states: valid, already used, cancelled, invalid, expired, wrong event

### Manual Sales

- Event and ticket type selection
- Buyer details
- Quantity
- Price mode
- Payment method
- Notes
- Issue as paid, unpaid, or reserved
- Print or send after creation

### Orders

- Unified orders list for ticket, book, mixed, manual, and online orders
- Filters by type, status, payment method, and source
- Drawer with lines, payment state, customer, notes, and fulfillment
- Actions: confirm payment, update status, cancel, refund, resend confirmation, print receipt or invoice

### Customers

- Searchable customer list
- Profile panel with contact info, notes, tags placeholder, purchase history, attendance history

### Contact Messages

- Inbox list with status chips
- Thread or detail panel
- Actions: mark read, unread, in progress, replied, closed, spam
- Reply composer with canned template slot prepared

### Payments

- Payment list with reference, source, customer, amount, method, and status
- Actions: reconcile, mark paid, mark failed, refund, attach note

### Reports

- Summary cards only in V1
- Daily, weekly, monthly toggles
- Export actions for backend CSV and PDF endpoints

### Settings

- Admin profile
- Change password
- Audit log placeholder if backend is not ready

## Backend API Contract

### Admin auth

- `POST /api/v1/admin/auth/login`
- `POST /api/v1/admin/auth/logout`
- `GET /api/v1/admin/auth/me`
- `POST /api/v1/admin/auth/change-password`

### Overview

- `GET /api/v1/admin/overview`

### Tickets

- `GET /api/v1/admin/tickets`
- `GET /api/v1/admin/tickets/{id}`
- `POST /api/v1/admin/tickets/manual-issue`
- `POST /api/v1/admin/tickets/{id}/validate`
- `POST /api/v1/admin/tickets/{id}/mark-used`
- `POST /api/v1/admin/tickets/{id}/void`
- `POST /api/v1/admin/tickets/{id}/reissue`
- `POST /api/v1/admin/tickets/{id}/resend`
- `GET /api/v1/admin/tickets/{id}/download`
- `GET /api/v1/admin/tickets/export`

### Orders

- `GET /api/v1/admin/orders`
- `GET /api/v1/admin/orders/{id}`
- `POST /api/v1/admin/orders/{id}/status`
- `POST /api/v1/admin/orders/{id}/confirm-payment`
- `POST /api/v1/admin/orders/{id}/refund`
- `POST /api/v1/admin/orders/{id}/cancel`
- `GET /api/v1/admin/orders/{id}/invoice`

### Customers

- `GET /api/v1/admin/customers`
- `GET /api/v1/admin/customers/{id}`

### Contact messages

- `GET /api/v1/admin/contact-messages`
- `GET /api/v1/admin/contact-messages/{id}`
- `POST /api/v1/admin/contact-messages/{id}/reply`
- `POST /api/v1/admin/contact-messages/{id}/status`

### Payments

- `GET /api/v1/admin/payments`
- `GET /api/v1/admin/payments/{id}`
- `POST /api/v1/admin/payments/{id}/reconcile`
- `POST /api/v1/admin/payments/{id}/refund`

### Reports

- `GET /api/v1/admin/reports/summary`
- `GET /api/v1/admin/reports/export`

## Admin Data and Auth Model

- one `admin_users` table
- one admin role only in V1
- separate admin auth from customer portal OTP auth
- Sanctum tokens for admin sessions
- no full RBAC matrix yet

## Status and Filter Defaults

These should be standardized in both UI mocks and backend responses early to avoid churn.

### Ticket status

- pending
- paid
- issued
- used
- cancelled
- refunded
- expired
- voided

### Ticket source

- online
- admin_manual
- complimentary

### Order type

- ticket_only
- book_only
- mixed
- manual
- online

### Order status

- pending
- paid
- processing
- completed
- cancelled
- refunded
- failed

### Payment status

- pending
- paid
- failed
- refunded
- partially_paid

### Contact message status

- unread
- read
- in_progress
- replied
- closed
- spam

## Test Plan

### Admin auth

- login
- logout
- protected-route redirect
- token restore
- invalid credentials
- password change

### Shell and navigation

- desktop sidebar
- mobile nav and more sheet
- active section state
- page headers
- drawer responsiveness

### Overview

- KPI cards
- recent orders and messages
- upcoming events from hardcoded config

### Tickets

- search
- filters
- badge states
- detail drawer
- resend, reissue, void flows

### Ticket validation

- valid
- already used
- invalid
- cancelled
- expired

### Manual sales

- create paid ticket
- create unpaid ticket
- ticket appears in tickets, orders, payments, and customer history

### Orders

- status update
- confirm payment
- refund
- cancel
- invoice trigger

### Contact

- inbox transitions
- reply flow

### Payments

- reconciliation
- refund state
- payment reference lookup

### Reports

- summary cards load
- export actions trigger without layout breakage

### UI quality

- no duplicate one-off widgets where shared primitives should be used
- mobile flows are operational, not just visible
- loading, empty, success, and error states are consistent

## Implementation Checklist

### Phase 1: UI Foundation

- [ ] Confirm the React frontend repo and branch that will own the `/admin` area
- [ ] Add the `/admin` route group and protected admin route wrapper
- [ ] Create `AdminShell`, `AdminSidebar`, `AdminHeader`, and `AdminPageHeader`
- [ ] Create shared admin primitives for table, filter bar, status badge, drawer, dialog, empty state, and skeletons
- [ ] Define the admin design tokens and spacing rules
- [ ] Add desktop sidebar behavior
- [ ] Add mobile nav or compact rail plus "More" sheet
- [ ] Create route shells for overview, tickets, validation, manual sales, orders, customers, contact, payments, reports, and settings
- [ ] Add a mock data adapter layer that mirrors the future admin API contracts

### Phase 2: Workflow-Complete UI

- [ ] Build overview KPI strip, recent orders, recent messages, upcoming events, and action queue
- [ ] Build tickets table, filters, search, drawer, and row actions
- [ ] Build ticket validation screen with manual code entry and result states
- [ ] Build manual sales POS-style form and post-create actions
- [ ] Build orders list, filters, drawer, and action dialogs
- [ ] Build customers list and profile panel
- [ ] Build contact inbox, message detail, and reply composer shell
- [ ] Build payments list and reconciliation action UI
- [ ] Build reports summary and export trigger UI
- [ ] Build settings screens for admin profile and password change
- [ ] Add loading, empty, success, and error states to every admin page
- [ ] Review for duplicated page-specific widgets and replace them with shared primitives

### Phase 3: Laravel Admin Auth and Read APIs

- [ ] Add `admin_users` migration
- [ ] Add `AdminUser` model
- [ ] Add admin auth routes under `/api/v1/admin/auth/*`
- [ ] Implement admin login endpoint
- [ ] Implement admin logout endpoint
- [ ] Implement admin `me` endpoint
- [ ] Implement admin change-password endpoint
- [ ] Add admin auth middleware and route protection
- [ ] Add `GET /api/v1/admin/overview`
- [ ] Add `GET /api/v1/admin/tickets`
- [ ] Add `GET /api/v1/admin/tickets/{id}`
- [ ] Add `GET /api/v1/admin/orders`
- [ ] Add `GET /api/v1/admin/orders/{id}`
- [ ] Add `GET /api/v1/admin/customers`
- [ ] Add `GET /api/v1/admin/customers/{id}`
- [ ] Add `GET /api/v1/admin/contact-messages`
- [ ] Add `GET /api/v1/admin/contact-messages/{id}`
- [ ] Add `GET /api/v1/admin/payments`
- [ ] Add `GET /api/v1/admin/payments/{id}`
- [ ] Add `GET /api/v1/admin/reports/summary`
- [ ] Return filter metadata and normalized statuses in response payloads
- [ ] Replace mock read adapters with live API integrations in the React admin app

### Phase 4: Laravel Mutations and Exports

- [ ] Add `POST /api/v1/admin/tickets/manual-issue`
- [ ] Add `POST /api/v1/admin/tickets/{id}/validate`
- [ ] Add `POST /api/v1/admin/tickets/{id}/mark-used`
- [ ] Add `POST /api/v1/admin/tickets/{id}/void`
- [ ] Add `POST /api/v1/admin/tickets/{id}/reissue`
- [ ] Add `POST /api/v1/admin/tickets/{id}/resend`
- [ ] Add `GET /api/v1/admin/tickets/{id}/download`
- [ ] Add `GET /api/v1/admin/tickets/export`
- [ ] Add `POST /api/v1/admin/orders/{id}/status`
- [ ] Add `POST /api/v1/admin/orders/{id}/confirm-payment`
- [ ] Add `POST /api/v1/admin/orders/{id}/refund`
- [ ] Add `POST /api/v1/admin/orders/{id}/cancel`
- [ ] Add `GET /api/v1/admin/orders/{id}/invoice`
- [ ] Add `POST /api/v1/admin/contact-messages/{id}/reply`
- [ ] Add `POST /api/v1/admin/contact-messages/{id}/status`
- [ ] Add `POST /api/v1/admin/payments/{id}/reconcile`
- [ ] Add `POST /api/v1/admin/payments/{id}/refund`
- [ ] Add `GET /api/v1/admin/reports/export`
- [ ] Wire mutation success and failure handling in the React admin app
- [ ] Refresh affected screens after mutations without full page reloads

### Phase 5: QA and Release Readiness

- [ ] Seed or prepare realistic admin demo data
- [ ] Test admin login, logout, session restore, and password change
- [ ] Test desktop and mobile admin navigation
- [ ] Test overview widgets against live backend data
- [ ] Test ticket search, filters, validation, void, reissue, resend, and download flows
- [ ] Test manual sales end-to-end across tickets, orders, payments, and customer history
- [ ] Test order status, confirm-payment, refund, cancel, and invoice flows
- [ ] Test contact message reply and status transition flows
- [ ] Test payment reconciliation and refund flows
- [ ] Test reports summary and export flows
- [ ] Verify loading, empty, success, and error states on all pages
- [ ] Verify responsive behavior on common mobile and desktop breakpoints
- [ ] Review sensitive actions for confirmation dialogs and auditability
- [ ] Freeze V1 scope and push all non-goals to the post-V1 backlog

## Codebase Checklist

This section maps the work to the actual Laravel codebase structure in this repo.

## Phased UI-First Approach Under This Codebase

If the decision is to implement the admin UI inside this Laravel repo, the build should still stay UI-first. That means we bootstrap the React admin shell and mocked workflows before we add most admin backend logic.

### Phase 0: Frontend Bootstrap Inside Laravel

- [ ] Add React dependencies to [`package.json`](C:/Users/Greenwebb/Desktop/Projects/zangi/codebase/backend/package.json)
- [ ] Add the React Vite plugin to [`vite.config.js`](C:/Users/Greenwebb/Desktop/Projects/zangi/codebase/backend/vite.config.js)
- [ ] Create `resources/js/admin/main.jsx`
- [ ] Create `resources/js/admin/App.jsx`
- [ ] Create `resources/js/admin/routes/`
- [ ] Create `resources/js/admin/components/`
- [ ] Create `resources/js/admin/features/`
- [ ] Create `resources/views/admin.blade.php`
- [ ] Add a Laravel web route that serves the admin SPA shell for `/admin` paths

### Phase 1: Admin Shell and Design System

- [ ] Create the admin route map in `resources/js/admin/routes/`
- [ ] Create `AdminShell`
- [ ] Create `AdminSidebar`
- [ ] Create `AdminHeader`
- [ ] Create `AdminPageHeader`
- [ ] Create shared admin card, table, badge, drawer, dialog, empty state, and skeleton components
- [ ] Add admin navigation state and active-route handling
- [ ] Add desktop and mobile admin navigation patterns
- [ ] Add page shells for overview, tickets, validation, manual sales, orders, customers, contact, payments, reports, and settings

### Phase 2: Mocked UI Workflows

- [ ] Create mock datasets under `resources/js/admin/features/*/mocks/`
- [ ] Create admin API adapters that can switch between mock and live data
- [ ] Build overview widgets against mock data
- [ ] Build tickets list, search, filters, drawer, and actions against mock data
- [ ] Build validation screen against mock ticket states
- [ ] Build manual sales form against mock submit flows
- [ ] Build orders, customers, contact, payments, reports, and settings screens against mock data
- [ ] Add loading, empty, success, and error states to every page
- [ ] Review the UI for reusable patterns before backend integration starts

### Phase 3: Admin Auth and Read APIs

- [ ] Add `/api/v1/admin/auth/*` routes in [`routes/api.php`](C:/Users/Greenwebb/Desktop/Projects/zangi/codebase/backend/routes/api.php)
- [ ] Add `AdminUser` model and `admin_users` migration
- [ ] Add admin auth controllers in `app/Http/Controllers/Api/V1/Admin/`
- [ ] Add admin request validation in `app/Http/Requests/Admin/`
- [ ] Add read endpoints for overview, tickets, orders, customers, contact messages, payments, and reports
- [ ] Normalize response payloads to match the mock adapter shapes
- [ ] Replace mock auth and read adapters with live API calls

### Phase 4: Mutations and Operational Actions

- [ ] Add ticket mutation endpoints
- [ ] Add manual issue endpoint
- [ ] Add order mutation endpoints
- [ ] Add contact reply and status endpoints
- [ ] Add payment reconcile and refund endpoints
- [ ] Add reports export endpoint
- [ ] Wire the UI action dialogs to the live endpoints
- [ ] Refresh affected page state after mutations

### Phase 5: QA, Hardening, and Release

- [ ] Add feature tests for admin auth and core admin endpoints
- [ ] Add realistic admin seed data
- [ ] Test all high-frequency admin workflows end to end
- [ ] Check responsive behavior on mobile and desktop
- [ ] Confirm sensitive action confirmations and audit placeholders
- [ ] Freeze V1 and move non-goals to post-V1 backlog

### Backend work in this repo

- [ ] Update [`routes/api.php`](C:/Users/Greenwebb/Desktop/Projects/zangi/codebase/backend/routes/api.php) with `/api/v1/admin/*` route groups
- [ ] Add [`app/Models/AdminUser.php`](C:/Users/Greenwebb/Desktop/Projects/zangi/codebase/backend/app/Models/AdminUser.php)
- [ ] Add `database/migrations/*_create_admin_users_table.php`
- [ ] Add admin auth controllers under `app/Http/Controllers/Api/V1/Admin/`
- [ ] Add admin domain controllers under `app/Http/Controllers/Api/V1/Admin/`
- [ ] Add admin request validation classes under `app/Http/Requests/Admin/`
- [ ] Add admin services under `app/Services/Admin/`
- [ ] Add admin-specific mail classes only where needed for resend or reply flows
- [ ] Add admin resource or transformer layer if response shaping starts to spread across controllers
- [ ] Add admin policy or gate checks if single-role rules need explicit enforcement
- [ ] Add admin test coverage under `tests/Feature/Api/V1/Admin/`
- [ ] Add seeders or factory support for admin demo and QA data

### Recommended Laravel file breakdown

- [ ] `app/Http/Controllers/Api/V1/Admin/AuthController.php`
- [ ] `app/Http/Controllers/Api/V1/Admin/OverviewController.php`
- [ ] `app/Http/Controllers/Api/V1/Admin/TicketController.php`
- [ ] `app/Http/Controllers/Api/V1/Admin/OrderController.php`
- [ ] `app/Http/Controllers/Api/V1/Admin/CustomerController.php`
- [ ] `app/Http/Controllers/Api/V1/Admin/ContactMessageController.php`
- [ ] `app/Http/Controllers/Api/V1/Admin/PaymentController.php`
- [ ] `app/Http/Controllers/Api/V1/Admin/ReportController.php`
- [ ] `app/Http/Requests/Admin/Auth/LoginRequest.php`
- [ ] `app/Http/Requests/Admin/Auth/ChangePasswordRequest.php`
- [ ] `app/Http/Requests/Admin/Tickets/TicketIndexRequest.php`
- [ ] `app/Http/Requests/Admin/Tickets/ManualIssueTicketRequest.php`
- [ ] `app/Http/Requests/Admin/Orders/OrderStatusUpdateRequest.php`
- [ ] `app/Http/Requests/Admin/Contact/ReplyContactMessageRequest.php`
- [ ] `app/Http/Requests/Admin/Payments/ReconcilePaymentRequest.php`
- [ ] `app/Services/Admin/AdminAuthService.php`
- [ ] `app/Services/Admin/AdminOverviewService.php`
- [ ] `app/Services/Admin/AdminTicketService.php`
- [ ] `app/Services/Admin/AdminOrderService.php`
- [ ] `app/Services/Admin/AdminCustomerService.php`
- [ ] `app/Services/Admin/AdminContactMessageService.php`
- [ ] `app/Services/Admin/AdminPaymentService.php`
- [ ] `app/Services/Admin/AdminReportService.php`

### If the admin React UI is also hosted inside this repo

The repo does not currently contain React. If the decision is to host the admin UI here instead of a separate frontend repo, this bootstrap work is required first.

- [ ] Add `react`, `react-dom`, `react-router-dom`, and `@vitejs/plugin-react`
- [ ] Update [`package.json`](C:/Users/Greenwebb/Desktop/Projects/zangi/codebase/backend/package.json) with React dependencies and scripts if needed
- [ ] Update [`vite.config.js`](C:/Users/Greenwebb/Desktop/Projects/zangi/codebase/backend/vite.config.js) to use the React Vite plugin
- [ ] Create a React entrypoint such as `resources/js/admin/main.jsx`
- [ ] Create a React app root such as `resources/js/admin/App.jsx`
- [ ] Create `resources/js/admin/routes/`
- [ ] Create `resources/js/admin/components/`
- [ ] Create `resources/js/admin/features/`
- [ ] Add a Blade mount view such as `resources/views/admin.blade.php`
- [ ] Add a web route that serves the admin SPA shell for `/admin` paths
- [ ] Keep admin API calls under `/api/v1/admin/*`, not mixed into web routes

### If the admin UI stays in a separate frontend repo

- [ ] Keep this Laravel repo focused on admin API, auth, exports, and business rules
- [ ] Document the shared admin API contract in this repo
- [ ] Keep mock payload shapes in sync with Laravel responses
- [ ] Version breaking admin API changes clearly before frontend integration

## Recommended Execution Order

1. Approve this scope and freeze V1 non-goals.
2. Build the admin shell and primitives in the React frontend with mock data.
3. Finish workflow-complete UI for all V1 screens.
4. Add admin auth and read endpoints in Laravel.
5. Replace mock adapters with real read APIs.
6. Add mutation endpoints and exports.
7. Run responsive, action, and regression testing.

## Final Recommendation

Yes, this should be done as a UI-first phased build.

That is the lowest-risk way to get a high-quality admin dashboard quickly while keeping the backend clean. The only important adjustment is architectural: this backend repo should own the admin API and auth, but the actual React admin UI should be built in the existing frontend codebase, because that app is not present inside this Laravel repo today.
