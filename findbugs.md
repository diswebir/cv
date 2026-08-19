# Bug Findings Report - Virtual Business Card Platform

*Generated: 2026-08-18*

---

## 🔴 Critical Security Issues (P0 - Immediate)

### 1. XSS in Card Templates (Reflected/Stored XSS)
**Files:** `app/views/layouts/public.php:38`, `app/templates/cards/_functions.php:139-140`
**Issue:** User-controlled data output in JavaScript context without proper escaping. Using `e()` (HTML escaping) for JS data-attributes.
**Impact:** XSS via malicious URLs in QR codes or card data.
**Fix:** Use `json_encode()` for data-* attributes or separate JS from HTML.

### 2. Path Traversal in File Upload/Delete
**File:** `includes/helpers.php:245-265` (`delete_upload` function)
**Issue:** Path normalization can be bypassed on Windows with `..` or symlinks. `VC_ROOT . '/' . $safePath` not validated with `realpath()`.
**Impact:** Arbitrary file deletion on server.
**Fix:** Validate resolved path is within uploads directory using `realpath()`.

### 3. CSRF Token Exposure in QR Generation Flow
**Files:** `app/controllers/card.php:96-97`, `assets/js/editor.js:183-189`
**Issue:** Form submission via `setTimeout` + `window.open` after save can bypass CSRF protection. No referrer/origin validation.
**Impact:** Attacker can trick user into editing cards.
**Fix:** Verify `Origin`/`Referer` headers or enforce SameSite=Strict cookies.

### 4. Rate Limiting Bypass in QR Generation (Weak Hash)
**File:** `app/controllers/card.php:69-75`
**Issue:** Using `md5(client_ip())` for rate limit keys. MD5 collisions possible, weak for security purposes.
**Impact:** Attacker can bypass QR generation rate limits.
**Fix:** Use SHA-256, increase window, add per-account limits.

### 5. Missing HttpOnly Flag in Session Cookie (Conditional)
**File:** `includes/init.php:14-24`
**Issue:** `secure` flag only set in HTTPS, but `httponly` always true. In local HTTP dev, cookie accessible to JavaScript.
**Impact:** Session hijacking in development environments.
**Fix:** Always enforce `httponly=true`, `secure=true` in production.

---

## 🟠 High Severity Bugs (P1 - This Week)

### 6. Race Condition in Card Creation (TOCTOU)
**File:** `app/controllers/user.php:182-199` (`handle_card_form`)
**Issue:** Check `get_card_by_code()` then `create_card()` not atomic. Concurrent requests can create duplicate codes.
**Impact:** Duplicate key errors or cards with same short code.
**Fix:** Catch `PDOException` with code 23000 (unique constraint), retry with new code in transaction.

### 7. Password Length Inconsistency (Register vs Login)
**Files:** `app/controllers/auth.php:86` vs `includes/models.php:125` (`register_user`)
**Issue:** Register requires 12 chars (`mb_strlen($pass) < 12`), but `register_user()` only validates 6 chars minimum.
**Impact:** Users with 6-11 char passwords can register but cannot login.
**Fix:** Centralize password validation in one function, enforce consistent minimum (recommend 8+).

### 8. Duplicate updated_at Handling (DB vs Code)
**Files:** `includes/models.php:222-223`, `install.php:145`
**Issue:** Schema has `ON UPDATE CURRENT_TIMESTAMP` but code manually adds `updated_at = CURRENT_TIMESTAMP`. Breaks on PostgreSQL/SQLite.
**Impact:** Inconsistent behavior across database engines.
**Fix:** Remove manual update, rely on schema, or use ORM abstraction.

### 9. Install.php Self-Delete Vulnerability (Windows)
**File:** `install.php:184-196`
**Issue:** `rename(__FILE__, $deletedPath)` fails on Windows if file in use. `unlink` fallback has race condition.
**Impact:** Installer remains on server exposing sensitive setup.
**Fix:** Show warning to manually delete, don't attempt auto-delete.

### 10. Weak Base URL Validation in Admin Settings
**File:** `app/controllers/admin.php:178-180`
**Issue:** Reuses `sanitize_base_url()` from installer, not designed for SQL context validation.
**Impact:** Potential SQL injection if base_url used in raw queries.
**Fix:** Use prepared statements everywhere (already done in `set_setting`), strengthen validation.

---

## 🟡 Medium Severity Issues (P2 - This Month)

### 11. Memory Exhaustion in QR Generation
**File:** `includes/QRRenderer.php:74-75, 94`
**Issue:** `px` limited to 20, but `imagecreatetruecolor($side, $side)` where side = (modules + margin*2) * px. Version 40 QR = 177 modules → 3660px → ~50MB RAM per request.
**Impact:** DoS via concurrent large QR requests.
**Fix:** Harder limit (px ≤ 10), streaming output, or cache generated QR codes.

### 12. Inconsistent Password Validation Across Entry Points
**Files:** `app/controllers/auth.php:86` (register: 12 chars), `includes/auth.php:125` (`register_user`: 6 chars), `install.php:263` (admin: 8 chars)
**Issue:** Three different minimum password lengths in three places.
**Impact:** Bypass of stronger policy, confusion.
**Fix:** Single `validate_password()` function used everywhere.

### 13. No Database Connection Pooling
**File:** `includes/db.php:3-17`
**Issue:** New PDO connection per request. `static $pdo` only lives within request.
**Impact:** Poor performance under load, connection overhead.
**Fix:** Enable `PDO::ATTR_PERSISTENT => true` or use external pooler (PgBouncer).

### 14. Missing Index for Date-Based Visits Queries
**Files:** `install.php:151-161` (schema), `includes/models.php:314-318` (`visits_by_day`)
**Issue:** Query uses `DATE(visited_at)` which prevents index usage on `idx_visited`.
**Impact:** Full table scan on high-traffic sites.
**Fix:** Add generated column `visit_date DATE AS (DATE(visited_at)) STORED` + index, or composite index `(card_id, visited_at)`.

### 15. Hardcoded Timezone in Installer
**File:** `install.php:303`
**Issue:** `'timezone' => 'Asia/Tehran'` hardcoded, not configurable during install.
**Impact:** Non-Iran users get wrong dates.
**Fix:** Auto-detect from server or add timezone selection step.

### 16. QR Generation CPU DoS Potential
**Files:** `includes/QRRenderer.php:45-55`, `includes/qrcode/qrcode.php:127-149`
**Issue:** `getBestMaskPattern()` calls `makeImpl()` 8 times. Heavy for QR version 40.
**Impact:** CPU exhaustion under concurrent requests.
**Fix:** Cache QR matrices by content hash, limit max version, add request queuing.

---

## 🟢 Low Severity / Code Quality (P3 - Backlog)

### 17. Duplicated Array Processing Logic
**File:** `app/controllers/user.php:31-64` (`card_form_socials`, `card_form_custom`)
**Issue:** Nearly identical loops for processing `social_*` and `cf_*` POST arrays.
**Fix:** Create generic `process_array_input($prefix, $max, $sanitizer)`.

### 18. Inconsistent Error Handling Patterns
**Files:** All controllers
**Issue:** Mix of `try-catch Exception`, `try-catch PDOException`, no catch, silent failures.
**Fix:** Centralized exception handler or middleware for consistent error responses.

### 19. Magic Numbers in Rate Limiting
**Files:** `includes/auth.php:65-66`, `includes/helpers.php:315-329`
**Issue:** Hardcoded limits (10/900, 5/900, 5/3600, 30/60, 60/60) scattered.
**Fix:** Move to config file as named constants.

### 20. Missing Validation on Custom Fields (Stored XSS Risk)
**Files:** `app/views/panel/card_form.php:135-147`, `includes/models.php:218`
**Issue:** `cf_label`/`cf_value` stored via `json_encode(JSON_UNESCAPED_UNICODE)` without HTML sanitization. Rendered in templates via `e()` but context matters.
**Impact:** Stored XSS if custom field rendered in unsafe context.
**Fix:** Sanitize input in `handle_card_form()`, validate length/content.

### 21. No Automated Tests
**Project:** Zero unit, integration, or E2E tests.
**Impact:** Regressions undetectable, refactoring risky.
**Fix:** Add PHPUnit for backend, Playwright/Cypress for frontend.

### 22. Inconsistent Naming Conventions
**Project-wide:**
- Functions: `snake_case` (`get_user`, `create_card`) ✓
- Variables: Mixed `snake_case` and `camelCase` (`$userId` vs `$user_id`)
- Classes: `PascalCase` (`VQR`) ✓
**Fix:** Enforce PSR-12 (snake_case for functions/variables).

### 23. Missing Type Hints / DocBlocks
**Project:** No return types, parameter types, or PHPDoc on functions.
**Impact:** Poor IDE support, runtime type errors.
**Fix:** Add PHP 7.4+ typed properties, return types, parameter types.

### 24. JavaScript Code Duplication
**Files:** `assets/js/app.js:35-48` (toast), `assets/js/card.js:2-15` (toast + copy)
**Issue:** `showToast()` and `copyText()` duplicated.
**Fix:** Shared ES6 module or common utility file.

### 25. CSP Requires 'unsafe-inline' for Scripts
**File:** `includes/init.php:71-80`
**Issue:** `"script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net;"` needed due to inline scripts in templates.
**Impact:** Reduced CSP effectiveness.
**Fix:** Move all inline JS to external files, use nonce-based CSP.

---

## 📊 Priority Summary

| Priority | Count | Category |
|----------|-------|----------|
| **P0** | 5 | Critical Security |
| **P1** | 5 | High Severity Bugs |
| **P2** | 6 | Medium Issues |
| **P3** | 9 | Code Quality |
| **Total** | **25** | |

---

## ✅ Recommended Immediate Actions (Top 5)

1. **Fix XSS in data-attributes** → Use `json_encode()` for all `data-*` values
2. **Unify password validation** → Single `validate_password()` function, min 8 chars
3. **Fix race condition** → Catch `PDOException` code 23000 in `create_card()`
4. **Strengthen rate limiting** → SHA-256 keys, config-driven limits
5. **Path traversal protection** → `realpath()` validation in `delete_upload()`