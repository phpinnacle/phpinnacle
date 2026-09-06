# Refactor plan

Reviewed against the working tree on 2026-09-05. Preserve transform order, webhook response formats, handler contracts, and model-owned status transitions. Behavior fixes below precede cosmetic extraction.

## 1. Completed — render transform configuration as data

MOVE/CAST previews now pass configuration as data to static Blade components, and DATE formats are HTML-escaped. The public `preview(array): string` contract and normal badges and labels are preserved. `TransformConfigurationTest` covers literal HTML and Blade syntax alongside ordinary previews.

## 2. Priority: high — specify queue failure and dispatch lifecycle

`ProcessWebhook::failed(?Throwable)` forwards a nullable exception to `Webhook::fail()`, which immediately dereferences it. Narrowing only the model signature leaves the queue callback unresolved. Also, retry dispatch uses `afterCommit()`, while `WebhookObserver::created()` dispatches immediately.

- Define meaningful failure metadata for the nullable queue callback without inventing an exception or silently returning success. Keep the framework callback signature.
- Verify creation inside a transaction, rollback, successful processing, handler/transform failure, and retry dispatch. If initial dispatch can precede commit, fix it as a queue correctness change.
- Retain `complete()`, `progress()`, `fail()`, and `retry()` as model transitions. Their error-clearing and timestamp rules differ; a generic transition method is unnecessary unless it preserves those distinctions clearly.

Acceptance: failed jobs persist useful failed state, rolled-back creation is not processed, retries dispatch once after commit, and unexpected transactional exceptions propagate.

## 3. Priority: medium — share only equivalent JSONPath mutation mechanics

Characterize MAP/CAST/TRIM/DATE and object mutations with nested paths, multiple matches, missing paths, and configured date fallbacks. Then extract common lookup/write-back mechanics only where reference mutation semantics are identical. MOVE and DROP alter paths differently and need not use the same helper.

Acceptance: ordered transforms produce the same payloads and configured fallback outcomes; developer handlers and package-owned transform definitions are not reparsed defensively.

The review's `composer lint` also reports two redundant typed-state checks in `ManageWebhooks.php`; remove those locally when that error display is touched, without adding a new validation layer.
