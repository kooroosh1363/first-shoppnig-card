# Atelier Cart — Server-Side PHP Shopping Cart

[![Quality](https://github.com/kooroosh1363/first-shoppnig-card/actions/workflows/quality.yml/badge.svg)](https://github.com/kooroosh1363/first-shoppnig-card/actions/workflows/quality.yml)

Atelier Cart modernizes the original 2023 shopping-cart starter into a complete, session-backed PHP cart demo.

## What the original repository contained

The original project was mostly a skeleton:

- empty storefront body
- empty stylesheet
- Bootstrap and Bootstrap Icons loaded from a CDN
- a header with broken image sources
- a cart link pointing to a missing page
- no product model
- no cart state
- no add/update/remove logic
- no tests or CI

## Current capabilities

- server-owned product catalog
- session-backed cart state
- add to cart
- update quantity
- remove line
- clear cart
- stock-aware quantity caps
- per-product maximum limits
- integer-cent money calculations
- subtotal, shipping, and total
- free-shipping threshold
- CSRF protection for every cart mutation
- POST/Redirect/GET workflow
- hardened session cookie configuration
- responsive storefront and cart UI
- no frontend framework or runtime dependency
- PHP lint and cart-domain tests in GitHub Actions

## Why integer cents?

Money is represented as integers:

```text
$89.00 -> 8900
```

That avoids floating-point rounding problems in cart math.

## Cart architecture

```text
ProductCatalog
      │
      ▼
CartService
  ├── add()
  ├── update()
  ├── remove()
  ├── lines()
  └── totals()
      │
      ▼
PHP session
      │
      ▼
index.php / cart.php
```

Pricing and stock are always read from the server-side catalog. The browser only submits product IDs and quantities.

## Business rules

- unknown product IDs are rejected
- quantity must be a positive whole number when adding
- quantity 0 removes a line during update
- quantities are capped by both inventory and per-cart maximum
- shipping is $12 below a $180 subtotal
- shipping becomes free at or above $180
- checkout/payment is intentionally outside this demo

## Run locally

```bash
php -S localhost:8000
```

Then open:

```text
http://localhost:8000
```

## Tests

```bash
php tests/run.php
```

The suite covers:

- adding products
- inventory/max caps
- invalid product rejection
- invalid quantity rejection
- update/remove behavior
- integer-cent line totals
- shipping threshold rules
- normalized item counts

## Security notes

All cart mutations require POST plus a CSRF token. Session cookies use strict mode, HttpOnly, SameSite=Lax, and Secure when HTTPS is active.

The server never trusts submitted prices, product names, or stock values.

## Scope

This repository stops at a trustworthy cart boundary. It does **not** simulate checkout, payment processing, orders, taxes, inventory reservation, or fulfillment.

Those would require a larger transactional backend and, for payments, a real payment provider.

## Deployment

This is a PHP application, so GitHub Pages cannot execute it. Deploy to a PHP-capable host.

## License

No license is currently included.
