# upScale System Improvements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement security rate-limiting, system health diagnostics, automated testing harness, user management API enhancements, and UI/UX Pro Max polish.

**Architecture:** A layered approach adding security middleware (`RateLimiter`), an observability endpoint (`api/health.php`), a standalone zero-dependency TDD runner (`scripts/test.php`), and UI enhancements following the Data-Dense Dashboard design system.

**Tech Stack:** PHP 8+, SQLite / PDO, Vanilla JavaScript, Vanilla CSS, SVG Icons.

**Spec:** [docs/superpowers/specs/2026-10-02-upscale-system-improvements-design.md](file:///c:/Users/Isaias/Downloads/UPSCALE/docs/superpowers/specs/2026-10-02-upscale-system-improvements-design.md)

## Global Constraints
- Zero external package installation required: native PHP and vanilla web technologies.
- Backward compatibility with existing SQLite schema and `.env` configuration.
- UI changes must strictly use SVG icons, avoiding raw emojis in interactive controls.
- All tests must be runnable locally via `php scripts/test.php` without web server dependency.

## Review Focus
- Attempting 6 consecutive invalid logins must return HTTP 429 and block further attempts for decay period.
- Health endpoint must safely report SQLite connectivity and storage write permission without exposing secret tokens.
- Rate limiter cache must handle concurrent writes gracefully and clean up expired entries.
- UI buttons and modal close triggers must maintain a minimum touch target size of 44x44px on mobile viewports.
- All unit and integration tests must pass cleanly with exit code 0.

---

### Task 1: Lightweight Test Runner & Harness

**Files:**
- Create: `scripts/test.php`
- Create: `tests/TestCase.php`

**Interfaces:**
- Consumes: PHP standard library
- Produces: Test runner CLI returning 0 on all tests passing, 1 on failure.

- [ ] **Step 1: Create `tests/TestCase.php` with assert helpers**
  Implement assertions (`assertEquals`, `assertTrue`, `assertFalse`, `assertArrayHasKey`, `assertStringContains`).

- [ ] **Step 2: Create `scripts/test.php` runner**
  Discovers all `*Test.php` files in `tests/Unit/` and `tests/Integration/`, executes their test methods, and outputs colored terminal results.

- [ ] **Step 3: Verify test runner executes with 0 tests**
  Run: `php scripts/test.php`
  Expected: "No tests found" or "0 tests executed" with exit code 0.

---

### Task 2: Security Rate Limiter (TDD)

**Files:**
- Create: `tests/Unit/RateLimiterTest.php`
- Create: `src/Security/RateLimiter.php`
- Modify: `api/admin/login.php`

**Interfaces:**
- Consumes: `storage/cache/` file storage or SQLite
- Produces: `RateLimiter::isTooManyAttempts(string $key, int $maxAttempts, int $decaySeconds): bool`

- [ ] **Step 1: Write failing test in `tests/Unit/RateLimiterTest.php`**
  Test incrementing hits, hitting the threshold, and clearing after decay.

- [ ] **Step 2: Run test to verify it fails**
  Run: `php scripts/test.php`
  Expected: FAIL with "Class App\Security\RateLimiter not found"

- [ ] **Step 3: Implement `App\Security\RateLimiter`**
  Implement `hit()`, `attempts()`, `isTooManyAttempts()`, `availableIn()`, and `clear()`.

- [ ] **Step 4: Run test to verify it passes**
  Run: `php scripts/test.php`
  Expected: PASS

- [ ] **Step 5: Integrate `RateLimiter` into `api/admin/login.php`**
  Check `RateLimiter` before credentials check; record hit on failed attempt; clear on success; return HTTP 429 when locked out.

---

### Task 3: Health & Diagnostics Endpoint

**Files:**
- Create: `tests/Integration/HealthEndpointTest.php`
- Create: `api/health.php`

**Interfaces:**
- Consumes: `App\Database\Connection::make()`
- Produces: JSON payload `{ "status": "ok", "database": "connected", "latency_ms": 1.2, "php_version": "8.x", ... }`

- [ ] **Step 1: Write failing test in `tests/Integration/HealthEndpointTest.php`**
  Assert health payload structure and DB latency detection.

- [ ] **Step 2: Run test to verify it fails**
  Run: `php scripts/test.php`
  Expected: FAIL with file not found or class mismatch.

- [ ] **Step 3: Implement `api/health.php`**
  Checks DB connectivity with timing, storage write permissions, and PHP runtime details.

- [ ] **Step 4: Run test to verify it passes**
  Run: `php scripts/test.php`
  Expected: PASS

---

### Task 4: Partner Split Rules & Database Integrity Unit Tests

**Files:**
- Create: `tests/Unit/PartnerSplitRuleTest.php`
- Create: `tests/Integration/DatabaseIntegrityTest.php`

**Interfaces:**
- Consumes: `App\Repositories\PartnerSplitRuleRepository`, `storage/database.sqlite`
- Produces: Verified unit coverage of calculations and table schemas.

- [ ] **Step 1: Implement `PartnerSplitRuleTest.php`**
  Test gross minus fee, percentage calculation, and fixed fee deductions.

- [ ] **Step 2: Implement `DatabaseIntegrityTest.php`**
  Verify all expected tables exist in `storage/database.sqlite` (workspaces, users, customers, campaigns, upsell_offers).

- [ ] **Step 3: Run test runner to verify both pass**
  Run: `php scripts/test.php`
  Expected: All tests pass.

---

### Task 5: UI/UX Pro Max Modernization

**Files:**
- Modify: `index.html`
- Modify: `router.php` (route registration for `/api/health` if needed)

**Interfaces:**
- Consumes: CSS custom properties & SVG inline icons
- Produces: Accessible interactive buttons (minimum 44x44px touch targets) and SVG iconography.

- [ ] **Step 1: Audit and replace emoji icons in buttons with accessible SVG icons**
  Add inline SVG icons with `aria-hidden="true"` and proper accessibility labels.

- [ ] **Step 2: Apply 44px minimum touch targets and focus rings**
  Update button styles with `min-height: 44px; min-width: 44px; cursor: pointer;` and visible keyboard focus indicator `:focus-visible`.

- [ ] **Step 3: Verify in browser and execute full test suite**
  Run: `php scripts/test.php`
  Expected: 100% green.
