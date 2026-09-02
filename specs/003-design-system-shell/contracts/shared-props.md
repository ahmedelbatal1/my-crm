# Phase 1 Contract: Shared Props & Error Responses

**Feature**: `003-design-system-shell` | **Date**: 2026-09-02

This feature exposes no HTTP interface of its own — it adds no route and changes no existing one
(FR-043, SC-014). Its two server-side contracts are the **Inertia shared props** every page
receives and the **error responses** that now render as Inertia pages.

Both are directly asserted by `tests/Feature/SharedPropsTest.php` and
`tests/Feature/ErrorPageTest.php`.

---

## 1. Shared props

Added to `App\Http\Middleware\HandleInertiaRequests::share()` (research.md #6). Every existing
per-page prop is untouched.

### `auth`

```
auth: {
  user: {
    name: string,
    role: "admin" | "sales_rep",
  } | null
}
```

| Rule | Detail |
|---|---|
| Signed in | `auth.user` is an object carrying **only** `name` and `role` |
| Guest | `auth.user` is `null` — never an empty object, never absent |
| Exposure limit | No other User attribute is shared. Email, password hash, timestamps and id stay server-side, per the constitution's serialization-control requirement |
| Role rendering | The raw `role` value crosses the wire; the human label ("Admin" / "Sales rep") is produced client-side, so no raw enum string reaches a template (FR-016) |

### `flash`

```
flash: {
  success: string | null,
  warning: string | null,
  error:   string | null,
}
```

| Rule | Detail |
|---|---|
| Source | Read from the session (`$request->session()->get(...)`) on every response |
| Shape | All three keys always present; unset severities are `null` |
| Rendering | `FlashMessages.vue` renders each non-null value in the shell's message region with its severity treatment (FR-010) |
| Known boundary | **This feature produces no `success` message.** No controller flashes one today and FR-043 forbids editing controllers, so on delivery the region carries `error`/`warning` outcomes only. The contract supports all three so a later feature can flash success with no frontend change (research.md #8) |

### Guarantees for existing tests (FR-045)

Adding shared props must not disturb the 17 existing `assertInertia` assertions in feature 002's
suite. Those assert on named page props (`contacts`, `dealsByStage`, …) and are additive-safe:
new sibling keys at the props root do not affect `$page->has('contacts')` or
`$page->where('contacts.0.name', ...)` style assertions.

---

## 2. Error responses

Configured in `bootstrap/app.php`'s `withExceptions` closure (research.md #7).

| Status | Trigger | Response |
|---|---|---|
| 403 | Policy denial (including all four blocked deletions) | Inertia page `Errors/Error`, **status 403** |
| 404 | Unknown URL, missing model binding | Inertia page `Errors/Error`, **status 404** |
| 405 | Wrong HTTP verb on a known path | Inertia page `Errors/Error`, **status 405** |
| 419 | Expired session / CSRF token mismatch | Inertia page `Errors/Error`, **status 419** |
| 500 | Unhandled exception | Laravel default when `config('app.debug')` is true (stack trace preserved for local work); `Errors/Error` with status 500 otherwise |

### Page props

```
Errors/Error: {
  status: 403 | 404 | 405 | 419 | 500,
}
```

The page maps each status to a plain-English heading and explanation plus a route back — signed-in
users are offered the pipeline, guests the sign-in screen (using `auth.user` from §1). Copy is
per-status, so a 403 explains a permission problem and a 419 explains an expired session and asks
the user to sign in again (FR-041, US4 scenarios 7–8).

### Non-negotiable: status codes are preserved

The status code MUST be set explicitly on the Inertia response. Returning 200 with an error page
would break `assertForbidden()` and `assertNotFound()` across the existing suite **and** would
misreport a denial as a success to any client. `ErrorPageTest` asserts the page component and the
status code together, precisely so this cannot regress.

### Deliberately not excluded: the `testing` environment

The widely-copied recipe for Inertia error pages skips non-production environments. This contract
renders the page in **all** environments so the automated suite can assert on it; only the 500
case defers to Laravel's debug handler, and it defers on `app.debug` rather than on the
environment name.
