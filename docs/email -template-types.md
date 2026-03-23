# Zangi Email Template Types

## Scope
- This backend only needs transactional emails that match the current product.
- Products, events, and help content stay static in the frontend.
- Email should support the real backend workflows only: portal access, checkout confirmations, and contact follow-up.

## Templates We Need Now

### 1. Portal Access OTP
- Status: active
- Trigger:
  - manual portal login
  - manual signup
  - silent account creation after purchase
- Purpose:
  - verify the buyer email
  - let the buyer open the portal without a password

### 2. Book Order Confirmation
- Status: active
- Trigger:
  - COD hardcopy book order created
  - paid online book order finalized
- Purpose:
  - confirm the order was captured
  - show order reference, format, quantity, total, and payment state
  - point the buyer back to the portal using the same email

### 3. Event Ticket Confirmation
- Status: active
- Trigger:
  - paid event ticket purchase finalized
- Purpose:
  - confirm the ticket is ready
  - show event, ticket type, quantity, total, and ticket reference
  - tell the buyer the pass lives in the portal

### 4. Contact Message Receipt
- Status: active
- Trigger:
  - contact form submission stored successfully
- Purpose:
  - reassure the sender that the message was received
  - confirm the message is queued for follow-up

### 5. Contact Message Admin Alert
- Status: active
- Trigger:
  - contact form submission stored successfully
- Purpose:
  - notify the Zangi admin inbox about the new message
  - keep the sender details and message in email for quick reply

## Deferred Templates
- Payment failed notification
- Refund processed notice
- Hardcopy shipment / delivery updates
- Digital download ready email
- Event reminder
- Event update / venue change
- Event cancellation
- Ticket check-in confirmation

These are valid later, but they do not match the current backend workflow yet.

## Removed From Scope
- Password reset
- Password changed
- Login alert / new device
- Account activation / deactivation
- Newsletter
- Promotions / discounts
- Personalized recommendations
- Abandoned cart
- Loyalty / rewards
- Wishlist reminders
- Inactivity / re-engagement
- Suspicious activity alerts
- Email change confirmation
- Separate 2FA email
- API key / token emails

These do not belong in the current Zangi backend because:
- the portal uses email OTP, not passwords
- there is no wishlist, loyalty, or recommendation system
- there is no marketing-email system in this backend
- there is no developer portal or API key lifecycle

## Design Rules
- Use one shared branded HTML layout for all transactional emails.
- Keep email design consistent with the Zangi site:
  - warm off-white background
  - orange primary accents
  - deep slate body text
  - compact, friendly copy
- Do not rely on long marketing paragraphs.
- Every email should answer the next user question quickly:
  - what happened
  - what reference matters
  - what to do next

## Implementation Notes
- OTP is already live and should use the shared branded layout.
- Book order confirmation should support both:
  - paid online book orders
  - COD hardcopy orders
- Event ticket confirmation should only send after payment is verified.
- Contact emails should never block message storage if sending fails.
