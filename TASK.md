# TASK.md

# Print Order Configurator RTL for WooCommerce

---

# 0. Executive Summary

We are building a production-grade WooCommerce plugin for an Israeli print shop website.

This plugin is NOT a visual product designer.

This plugin is NOT Canva.

This plugin is NOT Lumise.

This plugin is a structured print order workflow system designed specifically for RTL Hebrew environments.

The plugin allows customers to:

* configure print product options,
* upload ready design files,
* or request design services,
* submit design instructions,
* upload logos/photos/references,
* complete checkout normally through WooCommerce.

The actual print preparation and graphic design work will be handled manually by the print shop team.

The plugin's goal is:

* reduce operational chaos,
* standardize print order intake,
* simplify customer workflow,
* support RTL safely,
* collect files securely,
* integrate deeply with WooCommerce,
* remain stable and maintainable long-term.

The plugin intentionally avoids dangerous and unnecessary complexity such as:

* canvas rendering,
* visual editors,
* PDF generation,
* live previews,
* drag-and-drop design systems,
* browser-based print rendering.

The system must feel reliable, boring, predictable, and operationally useful.

That is the correct architecture.

Especially in WordPress, where “quick hacks” eventually evolve into archaeological layers of suffering.

---

# 1. Product Goals

## Primary Business Goals

The plugin must:

1. Reduce incomplete or confusing print orders.
2. Allow customers to upload production-ready files safely.
3. Allow customers without designs to submit structured design briefs.
4. Improve internal workflow for managers and designers.
5. Reduce manual clarification work over WhatsApp/email.
6. Support Hebrew RTL production workflow correctly.
7. Be fast to launch and stable to maintain.

---

## Success Criteria

The MVP is successful if:

* customers can place print orders without confusion,
* staff can understand orders immediately,
* uploaded files are accessible and organized,
* RTL workflow is stable,
* checkout remains reliable,
* support requests decrease,
* the system works on mobile devices.

---

# 2. User Roles

The system must support these operational roles.

---

## 2.1 Customer

Can:

* configure print product,
* upload files,
* request design,
* add notes,
* complete checkout,
* receive order confirmation.

---

## 2.2 Print Manager

Can:

* review orders,
* verify uploaded files,
* download files,
* see customer brief,
* change internal workflow status,
* communicate with customer manually.

---

## 2.3 Designer

Can:

* review customer design brief,
* download customer assets,
* see design notes,
* upload revised files manually outside MVP if needed.

---

## 2.4 Site Administrator

Can:

* configure products,
* configure upload rules,
* configure pricing,
* manage plugin settings,
* manage workflow statuses.

---

# 3. Supported Product Types

The plugin must support multiple print product categories.

Examples:

* Business cards
* Flyers
* Brochures
* Magnets
* Stickers
* Posters
* Photo printing
* Menus
* Labels
* Booklets
* Rollup banners
* Custom print products

Each product may have different configuration options.

---

# 4. Pricing Modes

The system must support multiple business models.

---

## 4.1 Fixed Price Mode

Product has normal WooCommerce fixed price.

Optional services may add extra fees.

---

## 4.2 Configurable Price Mode

Selected options may affect price later.

MVP only requires support for:

* optional design service fee.

Advanced price matrices are future scope.

---

## 4.3 Quote Request Mode

Some products may not have automatic pricing.

Customer submits configuration and files.

Store staff manually reviews order and contacts customer with final price.

Plugin must be architected to support this later even if not fully implemented in MVP.

---

# 5. Core Workflow

---

## 5.1 Customer Product Page Flow

Customer opens WooCommerce product page.

Customer sees RTL configurator.

Customer selects:

* size,
* quantity,
* paper,
* print sides,
* lamination,
* corners,
* finishing options.

Then customer selects design workflow.

---

## 5.2 Ready Design Workflow

Hebrew label:

```text id="9zj9zv"
יש לי עיצוב מוכן
```

Customer uploads:

* PDF
* AI
* PSD
* EPS
* JPG
* PNG
* ZIP

Customer may upload multiple files.

Customer may add production notes.

---

## 5.3 Need Design Workflow

Hebrew label:

```text id="j1d07m"
אין לי עיצוב - אני צריך עיצוב מהדפוס
```

Customer fills design brief.

Fields:

* business name,
* phone,
* email,
* address,
* text for design,
* preferred colors,
* style,
* references,
* notes.

Customer may upload:

* logos,
* photos,
* reference images,
* inspiration files.

The print shop later creates the design manually.

---

# 6. Product Philosophy

The plugin must never pretend to automate design work that is actually done manually.

The plugin collects:

* structured data,
* files,
* instructions.

The plugin does NOT:

* replace designers,
* generate print-ready layouts,
* solve RTL canvas rendering,
* replace production workflow.

This is intentional.

---

# 7. Explicitly Forbidden Features

Do NOT build:

* canvas editor,
* visual drag/drop editor,
* PDF renderer,
* SVG renderer,
* live preview system,
* product designer,
* Canva clone,
* AI generator,
* customer-side print rendering,
* CMYK conversion,
* crop marks,
* template marketplace,
* Elementor integration,
* page builder integrations,
* React SPA frontend,
* overengineered frameworks.

This is NOT a web-to-print platform.

This is a print order workflow system.

---

# 8. RTL Requirements

The plugin is RTL-first.

Critical requirements:

* Hebrew UI
* RTL layout
* mixed Hebrew/English support
* numbers inside Hebrew text
* UTF-8 safety everywhere
* mobile RTL support

Critical test strings:

```text id="sxxg2m"
שלום Michael Design 054-1234567
```

```text id="b8xq3e"
מבצע מיוחד - Business Cards 500 יחידות - 054-1234567
```

These strings must display correctly in:

* product page,
* cart,
* checkout,
* order admin,
* emails.

---

# 9. Product Configuration

Each WooCommerce product may define:

* available sizes,
* available quantities,
* paper types,
* paper weights,
* print sides,
* lamination,
* corners,
* finishing options,
* upload rules,
* design service availability,
* design service fee,
* required fields.

---

# 10. Admin Operational Requirements

Managers must be able to:

* open order,
* immediately understand what customer ordered,
* download files quickly,
* see if customer needs design,
* see all customer instructions,
* see all production parameters,
* mark workflow status internally.

The admin must NOT need to inspect raw serialized meta.

---

# 11. Internal Workflow Statuses

The plugin should be architected for internal statuses.

Potential statuses:

```text id="8q6t10"
קבצים התקבלו
ממתין לעיצוב
ממתין לאישור לקוח
מוכן להדפסה
נשלח להדפסה
```

MVP may implement these as simple order meta.

Full workflow engine is future scope.

---

# 12. Edge Cases

The plugin must correctly handle:

* customer uploads 20+ files,
* interrupted upload,
* missing upload,
* Hebrew filenames,
* mixed Hebrew/English filenames,
* mobile uploads,
* large uploads,
* invalid file types,
* duplicated uploads,
* customer selects ready design but uploads nothing,
* customer submits long Hebrew text,
* Safari mobile uploads,
* Chrome uploads,
* RTL rendering on mobile devices.

---

# 13. Plugin Architecture

Suggested structure:

```text id="61h0nj"
print-order-configurator-rtl/
│
├── print-order-configurator-rtl.php
├── README.md
├── TASK.md
├── CHANGELOG.md
├── LICENSE
├── SECURITY.md
├── readme.txt
├── .gitignore
│
├── includes/
│   ├── class-plugin.php
│   ├── class-product-settings.php
│   ├── class-frontend.php
│   ├── class-cart.php
│   ├── class-order.php
│   ├── class-upload-handler.php
│   ├── class-admin-order.php
│   ├── class-pricing.php
│   ├── class-statuses.php
│   └── helpers.php
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── templates/
│
├── languages/
│
└── uninstall.php
```

No unnecessary frameworks.

Vanilla JS preferred.

---

# 14. WordPress.org Readiness

The plugin must be written as if it may later be submitted to WordPress.org.

Requirements:

* GPL-compatible,
* no obfuscated code,
* no telemetry,
* no remote code execution,
* no hidden tracking,
* proper readme.txt,
* proper licensing,
* translation-ready,
* uninstall cleanup support,
* coding standards compliance.

---

# 15. Coding Standards

Must follow:

* WordPress Coding Standards,
* WooCommerce standards,
* modern PHP practices.

Target compatibility:

```text id="cf6ap9"
PHP 8.1+
Latest WordPress
Latest WooCommerce
```

---

# 16. Security Requirements

CRITICAL.

Must implement:

* nonce validation,
* capability checks,
* escaping,
* sanitization,
* upload validation,
* MIME validation,
* XSS prevention,
* CSRF protection,
* protected downloads.

Reject dangerous uploads:

```text id="4grg3w"
php
phtml
phar
exe
js
sh
bat
```

SVG disabled by default unless safely sanitized.

Never expose local server paths publicly.

---

# 17. Performance Requirements

The plugin must:

* load assets only where needed,
* avoid frontend bloat,
* avoid unnecessary queries,
* avoid heavy frameworks,
* work on shared hosting,
* avoid large JS bundles.

---

# 18. Accessibility Requirements

Must support:

* proper labels,
* keyboard navigation,
* visible validation,
* mobile usability,
* accessible forms.

---

# 19. Repository Rules

Before development:

1. Create Git repository.
2. Create GitHub repository immediately.
3. Push initial scaffold before features.

Recommended repository:

```text id="7b3n9y"
print-order-configurator-rtl
```

Public preferred.

Private allowed if required.

---

# 20. AI Development Workflow

The AI must work incrementally.

The AI must NOT build the entire plugin at once.

Correct workflow:

```text id="2bnx6z"
Read task
Implement ONLY this task
Verify
Commit
Push
Return to TASK.md
Read next task
Repeat
```

---

# 21. Mandatory Verification

After every task verify:

* plugin activates,
* admin loads,
* WooCommerce works,
* cart works,
* checkout works,
* no PHP warnings,
* no JS errors,
* RTL intact.

---

# 22. Commit Discipline

Every task = separate commit.

Examples:

```text id="5t7s6f"
Initial plugin scaffold
Add WooCommerce product settings
Add frontend RTL configurator
Implement upload validation
Save configurator data to cart
Save configurator data to orders
Add admin order panel
Add workflow statuses
Security hardening
RTL polish
```

---

# 23. MVP Scope

MVP MUST include:

* product configuration,
* RTL frontend,
* upload system,
* design request workflow,
* cart integration,
* checkout integration,
* order metadata,
* admin order panel,
* secure uploads,
* design service fee,
* mobile usability.

---

# 24. Explicit Non-MVP Scope

NOT part of MVP:

* visual editor,
* PDF generation,
* customer project system,
* advanced workflow engine,
* cloud storage,
* advanced pricing matrices,
* customer proof approval,
* production exports,
* React frontend,
* AI integrations.

---

# 25. Development Steps

---

## Step 1 - Plugin Scaffold

Acceptance:

* plugin activates,
* WooCommerce dependency check works,
* no fatal errors.

Commit + push.

---

## Step 2 - Product Settings

Acceptance:

* product fields save/load correctly,
* sanitization works.

Commit + push.

---

## Step 3 - Frontend RTL Configurator

Acceptance:

* RTL layout works,
* mobile works,
* design workflow toggle works.

Commit + push.

---

## Step 4 - Validation Layer

Acceptance:

* invalid submissions blocked,
* Hebrew validation messages displayed.

Commit + push.

---

## Step 5 - Cart Metadata

Acceptance:

* configuration stored in cart,
* Hebrew preserved.

Commit + push.

---

## Step 6 - Order Metadata

Acceptance:

* admin sees structured order information.

Commit + push.

---

## Step 7 - Upload Handler

Acceptance:

* secure uploads work,
* invalid files rejected,
* downloads available for admin.

Commit + push.

---

## Step 8 - Design Service Pricing

Acceptance:

* design fee works correctly,
* no duplicate calculations.

Commit + push.

---

## Step 9 - Admin Order Panel

Acceptance:

* readable order UI,
* downloadable files,
* clear workflow visibility.

Commit + push.

---

## Step 10 - Hardening and Polish

Acceptance:

* no fatal errors,
* no warnings,
* no JS errors,
* stable checkout,
* RTL polished,
* mobile verified.

Commit + push.

---

# 26. Product-Level Acceptance Criteria

The plugin is successful only if:

* customer can place order without confusion,
* manager understands order in under 30 seconds,
* designer receives enough information to start work,
* uploads are reliable,
* mobile workflow works,
* Hebrew workflow is stable,
* checkout remains trustworthy.

---

# 27. Definition of Done

The MVP is complete only if:

1. Customers can configure print products.
2. Customers can upload files.
3. Customers can request design service.
4. RTL layout works correctly.
5. Cart integration works.
6. Checkout works.
7. Order metadata works.
8. Admin can download files.
9. Hebrew text preserved correctly.
10. Security checks implemented.
11. No overengineered nonsense exists.

---

# 28. Future Roadmap

Only AFTER stable MVP.

---

## Phase 2

* reusable option presets,
* drag/drop uploads,
* ZIP downloads,
* internal workflow UI.

---

## Phase 3

* customer proof approval,
* notifications,
* production exports,
* internal designer notes.

---

## Phase 4

* pricing matrices,
* advanced calculations,
* production automation.

---

## Phase 5

* optional lightweight preview system.

NOT a full visual editor.

---

# 29. Final Engineering Philosophy

The goal is not impressive frontend demos.

The goal is:

* stable operations,
* reliable RTL workflow,
* maintainable architecture,
* secure uploads,
* predictable WooCommerce behavior,
* operational simplicity.

The plugin should feel boring in the best possible way.

Because boring software survives production.

Especially in WordPress.
Where one reckless plugin can turn checkout into a live-fire experiment.
