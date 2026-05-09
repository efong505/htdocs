# WP License Platform — Project Plan

## Table of Contents

1. [Project Overview](#project-overview)
2. [Architecture](#architecture)
3. [Database Schema](#database-schema)
4. [Plugin Design](#plugin-design)
5. [File Structure](#file-structure)
6. [Implementation Phases](#implementation-phases)
7. [Testing Plan](#testing-plan)

---

## Project Overview

**Plugin Name:** WP License Platform
**Slug:** wp-license-platform
**Purpose:** A WordPress plugin that turns your website into a digital product store with PayPal payment processing, license key management, VAT compliance, and a customer portal.

### What It Does
- Manages a catalog of digital products (plugins, themes, etc.)
- Processes payments via PayPal REST API
- Collects VAT evidence (billing address + IP geolocation) for HMRC/EU compliance
- Calculates and applies correct VAT rates per country
- Generates and manages license keys
- Provides a REST API for license validation (called by your Pro plugins)
- Hosts a customer portal (view licenses, download files, manage account)
- Generates PDF invoices
- Handles subscription renewals
- Sends transactional emails (purchase confirmation, renewal reminders, expiry notices)

### What It Does NOT Do
- Does not handle the Pro plugin features themselves (that's the Pro plugin's job)
- Does not require any external SaaS (everything runs on your WordPress site)
- Does not store credit card numbers (PayPal handles that)
- Does not use any third-party license management service

### Who Uses It
- **You (admin):** Manage products, view sales, handle support
- **Customers:** Purchase products, manage licenses, download files
- **Pro plugins:** Call the REST API to validate license keys

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    Your Website (ekewaka.com)                    │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                  WP License Platform Plugin                 │ │
│  │                                                             │ │
│  │  ┌─────────────┐  ┌──────────────┐  ┌──────────────────┐  │ │
│  │  │ Admin Panel  │  │ Public Pages │  │ REST API         │  │ │
│  │  │ - Products   │  │ - Checkout   │  │ - /validate      │  │ │
│  │  │ - Orders     │  │ - Thank You  │  │ - /activate      │  │ │
│  │  │ - Licenses   │  │ - Portal     │  │ - /deactivate    │  │ │
│  │  │ - Customers  │  │ - Downloads  │  │                  │  │ │
│  │  │ - Reports    │  │              │  │                  │  │ │
│  │  └──────┬───────┘  └──────┬───────┘  └────────┬─────────┘  │ │
│  │         │                 │                    │            │ │
│  │  ┌──────▼─────────────────▼────────────────────▼─────────┐  │ │
│  │  │                    Core Services                       │  │ │
│  │  │                                                        │  │ │
│  │  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ │  │ │
│  │  │  │ PayPal   │ │ VAT      │ │ License  │ │ Email    │ │  │ │
│  │  │  │ Client   │ │ Engine   │ │ Manager  │ │ Service  │ │  │ │
│  │  │  └────┬─────┘ └────┬─────┘ └────┬─────┘ └────┬─────┘ │  │ │
│  │  │       │            │            │            │        │  │ │
│  │  │  ┌────▼─────┐ ┌────▼─────┐ ┌────▼─────┐ ┌────▼─────┐ │  │ │
│  │  │  │ Invoice  │ │ GeoIP    │ │ Key Gen  │ │ Template │ │  │ │
│  │  │  │ Generator│ │ Lookup   │ │          │ │ Engine   │ │  │ │
│  │  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘ │  │ │
│  │  └────────────────────────────────────────────────────────┘  │ │
│  │                                                             │ │
│  │  ┌──────────────────────────────────────────────────────┐   │ │
│  │  │              Custom Database Tables                   │   │ │
│  │  │  wplp_products | wplp_orders | wplp_licenses         │   │ │
│  │  │  wplp_activations | wplp_vat_evidence                │   │ │
│  │  └──────────────────────────────────────────────────────┘   │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
└──────────────────────┬───────────────────────────────────────────┘
                       │
          ┌────────────┼────────────┐
          │            │            │
          ▼            ▼            ▼
    ┌──────────┐ ┌──────────┐ ┌──────────────┐
    │ PayPal   │ │ IP API   │ │ Customer's   │
    │ REST API │ │ (GeoIP)  │ │ WordPress    │
    │          │ │          │ │ Site         │
    │ Payment  │ │ Country  │ │             │
    │ Process  │ │ Lookup   │ │ Pro Plugin  │
    │ Webhooks │ │ for VAT  │ │ calls /validate │
    └──────────┘ └──────────┘ └──────────────┘
```

---

## Database Schema

The plugin creates custom tables (not wp_options) for performance and data integrity.

### wplp_products

Stores your digital products (e.g., "WP S3 Backup Pro").

```sql
CREATE TABLE {prefix}wplp_products (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    slug            VARCHAR(255) NOT NULL UNIQUE,
    description     TEXT,
    version         VARCHAR(20) DEFAULT '1.0.0',
    file_path       VARCHAR(500),              -- Path to downloadable zip
    status          ENUM('active','inactive') DEFAULT 'active',
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME NOT NULL,
    INDEX idx_slug (slug),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### wplp_product_tiers

Pricing tiers for each product (Personal, Professional, Agency).

```sql
CREATE TABLE {prefix}wplp_product_tiers (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(100) NOT NULL,     -- 'personal', 'professional', 'agency'
    display_name    VARCHAR(100) NOT NULL,     -- 'Personal', 'Professional', 'Agency'
    price           DECIMAL(10,2) NOT NULL,    -- 49.00, 99.00, 149.00
    currency        CHAR(3) DEFAULT 'USD',
    billing_period  ENUM('monthly','annual','lifetime') DEFAULT 'annual',
    sites_allowed   INT UNSIGNED DEFAULT 1,    -- 1, 5, 25, 0=unlimited
    is_featured     TINYINT(1) DEFAULT 0,      -- Highlight on pricing page
    sort_order      INT DEFAULT 0,
    status          ENUM('active','inactive') DEFAULT 'active',
    created_at      DATETIME NOT NULL,
    FOREIGN KEY (product_id) REFERENCES {prefix}wplp_products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### wplp_customers

Customer accounts (linked to WordPress users when possible).

```sql
CREATE TABLE {prefix}wplp_customers (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wp_user_id      BIGINT UNSIGNED DEFAULT NULL,  -- Link to WP user if registered
    email           VARCHAR(255) NOT NULL UNIQUE,
    first_name      VARCHAR(100),
    last_name       VARCHAR(100),
    company         VARCHAR(255),
    country_code    CHAR(2),                   -- ISO 3166-1 alpha-2
    vat_number      VARCHAR(50),               -- EU VAT number if provided
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME NOT NULL,
    INDEX idx_email (email),
    INDEX idx_wp_user (wp_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### wplp_orders

Purchase records.

```sql
CREATE TABLE {prefix}wplp_orders (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number    VARCHAR(50) NOT NULL UNIQUE,   -- WPLP-20260415-001
    customer_id     BIGINT UNSIGNED NOT NULL,
    product_id      BIGINT UNSIGNED NOT NULL,
    tier_id         BIGINT UNSIGNED NOT NULL,
    status          ENUM('pending','completed','refunded','failed') DEFAULT 'pending',
    subtotal        DECIMAL(10,2) NOT NULL,        -- Price before tax
    tax_amount      DECIMAL(10,2) DEFAULT 0.00,    -- VAT/tax amount
    tax_rate        DECIMAL(5,2) DEFAULT 0.00,     -- Tax rate applied (e.g., 20.00)
    tax_country     CHAR(2),                       -- Country tax was calculated for
    total           DECIMAL(10,2) NOT NULL,        -- subtotal + tax
    currency        CHAR(3) DEFAULT 'USD',
    paypal_order_id VARCHAR(100),                  -- PayPal order ID
    paypal_capture_id VARCHAR(100),                -- PayPal capture ID
    ip_address      VARCHAR(45),                   -- For VAT evidence
    billing_country CHAR(2),                       -- For VAT evidence
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES {prefix}wplp_customers(id),
    FOREIGN KEY (product_id) REFERENCES {prefix}wplp_products(id),
    FOREIGN KEY (tier_id) REFERENCES {prefix}wplp_product_tiers(id),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_paypal (paypal_order_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### wplp_licenses

License keys generated for each purchase.

```sql
CREATE TABLE {prefix}wplp_licenses (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_key     VARCHAR(50) NOT NULL UNIQUE,   -- WPS3B-XXXX-XXXX-XXXX
    order_id        BIGINT UNSIGNED NOT NULL,
    customer_id     BIGINT UNSIGNED NOT NULL,
    product_id      BIGINT UNSIGNED NOT NULL,
    tier_id         BIGINT UNSIGNED NOT NULL,
    status          ENUM('active','expired','revoked','suspended') DEFAULT 'active',
    sites_allowed   INT UNSIGNED DEFAULT 1,
    sites_active    INT UNSIGNED DEFAULT 0,
    expires_at      DATETIME,                      -- NULL = lifetime
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME NOT NULL,
    FOREIGN KEY (order_id) REFERENCES {prefix}wplp_orders(id),
    FOREIGN KEY (customer_id) REFERENCES {prefix}wplp_customers(id),
    FOREIGN KEY (product_id) REFERENCES {prefix}wplp_products(id),
    FOREIGN KEY (tier_id) REFERENCES {prefix}wplp_product_tiers(id),
    INDEX idx_key (license_key),
    INDEX idx_customer (customer_id),
    INDEX idx_product (product_id),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### wplp_activations

Tracks which sites have activated a license.

```sql
CREATE TABLE {prefix}wplp_activations (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id      BIGINT UNSIGNED NOT NULL,
    site_url        VARCHAR(500) NOT NULL,
    activated_at    DATETIME NOT NULL,
    last_checked    DATETIME,
    FOREIGN KEY (license_id) REFERENCES {prefix}wplp_licenses(id) ON DELETE CASCADE,
    INDEX idx_license (license_id),
    UNIQUE idx_license_site (license_id, site_url(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### wplp_vat_evidence

Stores the two pieces of location evidence required by HMRC for digital goods.

```sql
CREATE TABLE {prefix}wplp_vat_evidence (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id        BIGINT UNSIGNED NOT NULL,
    evidence_type   VARCHAR(50) NOT NULL,      -- 'billing_address', 'ip_geolocation', 'bank_country'
    country_code    CHAR(2) NOT NULL,          -- ISO country code
    raw_data        TEXT,                      -- Full evidence data (JSON)
    created_at      DATETIME NOT NULL,
    FOREIGN KEY (order_id) REFERENCES {prefix}wplp_orders(id) ON DELETE CASCADE,
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Plugin Design

### Core Classes

| Class | Responsibility |
|-------|---------------|
| `WPLP_Plugin` | Main entry point — activation, deactivation, table creation, menu registration |
| `WPLP_PayPal` | PayPal REST API client — create orders, capture payments, handle webhooks |
| `WPLP_VAT` | VAT rate lookup, evidence collection, IP geolocation, validation |
| `WPLP_License` | License key generation, validation, activation, deactivation, expiry |
| `WPLP_Order` | Order creation, status management, order number generation |
| `WPLP_Customer` | Customer CRUD, WordPress user linking |
| `WPLP_Invoice` | PDF invoice generation using HTML-to-PDF |
| `WPLP_Email` | Transactional emails — purchase confirmation, renewal, expiry |
| `WPLP_API` | REST API endpoints for license validation |
| `WPLP_Checkout` | Public checkout page — product selection, PayPal button, VAT form |
| `WPLP_Portal` | Customer portal — view licenses, downloads, account management |
| `WPLP_Admin_Products` | Admin UI for managing products and tiers |
| `WPLP_Admin_Orders` | Admin UI for viewing and managing orders |
| `WPLP_Admin_Licenses` | Admin UI for viewing and managing licenses |
| `WPLP_Admin_Reports` | Sales reports and analytics |

### WordPress Integration

| WP Feature | How We Use It |
|------------|---------------|
| Custom tables | All data in custom tables (not wp_options) for performance |
| REST API | License validation endpoints under `/wp-json/wplp/v1/` |
| WP-Cron | License expiry checks, renewal reminders, cleanup |
| wp_mail | Transactional emails |
| Shortcodes | `[wplp_checkout]`, `[wplp_portal]`, `[wplp_pricing]` |
| User roles | Custom `wplp_customer` role for portal access |
| Nonces | All forms and AJAX calls |
| Capabilities | `manage_wplp` for admin access |

---

## File Structure

```
wp-license-platform/
├── wp-license-platform.php           # Main plugin file
├── uninstall.php                      # Clean removal
├── readme.txt
├── LICENSE
│
├── includes/
│   ├── class-wplp-plugin.php              # Main class — init, activation, tables
│   ├── class-wplp-db.php                  # Database helper — table creation, queries
│   ├── class-wplp-paypal.php              # PayPal REST API client
│   ├── class-wplp-vat.php                 # VAT rates, evidence, geolocation
│   ├── class-wplp-license.php             # License key CRUD + validation
│   ├── class-wplp-order.php               # Order CRUD + status management
│   ├── class-wplp-customer.php            # Customer CRUD
│   ├── class-wplp-invoice.php             # PDF invoice generation
│   ├── class-wplp-email.php               # Transactional email templates
│   ├── class-wplp-api.php                 # REST API endpoints
│   ├── class-wplp-checkout.php            # Public checkout logic
│   └── class-wplp-portal.php              # Customer portal logic
│
├── admin/
│   ├── class-wplp-admin.php               # Admin menu registration
│   ├── class-wplp-admin-products.php      # Product management UI
│   ├── class-wplp-admin-orders.php        # Order management UI
│   ├── class-wplp-admin-licenses.php      # License management UI
│   ├── class-wplp-admin-settings.php      # Platform settings (PayPal creds, VAT, email)
│   ├── class-wplp-admin-reports.php       # Sales reports
│   ├── views/
│   │   ├── products-list.php
│   │   ├── product-edit.php
│   │   ├── orders-list.php
│   │   ├── order-detail.php
│   │   ├── licenses-list.php
│   │   ├── license-detail.php
│   │   ├── settings-page.php
│   │   └── reports-page.php
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
│
├── public/
│   ├── views/
│   │   ├── checkout.php                   # Checkout form + PayPal button
│   │   ├── thank-you.php                  # Post-purchase page
│   │   ├── portal-dashboard.php           # Customer dashboard
│   │   ├── portal-licenses.php            # License list
│   │   ├── portal-downloads.php           # Download files
│   │   ├── portal-invoices.php            # Invoice list
│   │   └── pricing-table.php             # Embeddable pricing table
│   ├── css/
│   │   └── public.css
│   └── js/
│       └── checkout.js                    # PayPal JS SDK integration
│
├── templates/
│   ├── emails/
│   │   ├── purchase-confirmation.php
│   │   ├── license-key-delivery.php
│   │   ├── renewal-reminder.php
│   │   ├── license-expired.php
│   │   └── refund-confirmation.php
│   └── invoices/
│       └── invoice-template.php           # HTML invoice (converted to PDF)
│
└── languages/
    └── wp-license-platform.pot
```

---

## Implementation Phases

### Phase 1: Foundation
- [ ] Plugin scaffolding (main file, constants, autoloading)
- [ ] Database table creation on activation
- [ ] Admin menu registration
- [ ] Product CRUD (admin UI + database)
- [ ] Product tier CRUD
- [ ] Settings page (PayPal credentials, business info, VAT settings)
- [ ] Settings encryption (reuse AES-256-CBC approach from WP S3 Backup)

### Phase 2: PayPal Integration
- [ ] PayPal REST API client (OAuth token, create order, capture payment)
- [ ] Checkout page with PayPal JavaScript SDK
- [ ] PayPal webhook handler (payment completed, refunded)
- [ ] Order creation and status management
- [ ] Customer creation (auto-create on first purchase)
- [ ] Thank you page with order details

### Phase 3: License System
- [ ] License key generation (format: PROD-XXXX-XXXX-XXXX)
- [ ] License creation on successful payment
- [ ] REST API: /wplp/v1/validate
- [ ] REST API: /wplp/v1/activate
- [ ] REST API: /wplp/v1/deactivate
- [ ] Site activation tracking
- [ ] License expiry cron job

### Phase 4: VAT Compliance
- [ ] VAT rate database (EU countries + UK + others)
- [ ] IP geolocation for country detection (free API)
- [ ] Billing address collection on checkout
- [ ] Two-piece evidence storage (IP + billing address)
- [ ] VAT calculation and application at checkout
- [ ] EU VAT number validation (VIES API)
- [ ] Reverse charge for valid EU VAT numbers (B2B)
- [ ] VAT evidence report for HMRC

### Phase 5: Customer Experience
- [ ] Customer portal (shortcode-based pages)
- [ ] License management (view, download, deactivate sites)
- [ ] File downloads with token-based access
- [ ] Purchase confirmation email
- [ ] License key delivery email
- [ ] Renewal reminder emails (30 days, 7 days, 1 day before expiry)
- [ ] License expired email

### Phase 6: Invoicing & Reports
- [ ] HTML invoice template
- [ ] PDF generation (using DomPDF or browser print)
- [ ] Invoice download from customer portal
- [ ] Admin sales reports (revenue, orders, customers)
- [ ] Export to CSV

### Phase 7: Subscriptions
- [ ] PayPal subscription plans
- [ ] Automatic renewal processing
- [ ] Failed payment handling
- [ ] Subscription cancellation
- [ ] Grace period on failed renewal

---

## Testing Plan

### Unit Tests
- License key generation uniqueness
- VAT rate lookup accuracy
- Order number generation
- PayPal signature verification

### Integration Tests
- Full purchase flow (checkout → PayPal → webhook → license)
- License validation API (valid, expired, revoked, over-limit)
- Site activation/deactivation
- VAT calculation for different countries
- Email delivery

### Manual Tests
- Complete purchase with PayPal sandbox
- Customer portal navigation
- Admin product/order/license management
- Invoice generation and download
- License expiry and renewal flow
- Refund processing
- VAT evidence report generation
