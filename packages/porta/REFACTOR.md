# Refactor plan

Reviewed against the working tree on 2026-09-05. Preserve transform order, webhook response formats, handler contracts, and model-owned status transitions. Behavior fixes below precede cosmetic extraction.

## 1. Completed — render transform configuration as data

MOVE/CAST previews now pass configuration as data to static Blade components, and DATE formats are HTML-escaped. The public `preview(array): string` contract and normal badges and labels are preserved. `TransformConfigurationTest` covers literal HTML and Blade syntax alongside ordinary previews.

## 2. Completed — specify queue failure and dispatch lifecycle

Initial dispatch and retry both wait for the outer transaction to commit. Rolled-back creation or retry does not run a handler. A nullable queue failure persists failed status, a timestamp, and an explicit missing-details message with null exception metadata. Existing exception and validation details are retained.

`WebhookLifecycleTest` exercises actual synchronous queue dispatch through commit/rollback, ordered transformation, completion cleanup, handler/payload failures, nullable failure callbacks, and retry dispatch exactly once.

## 3. Priority: medium — share only equivalent JSONPath mutation mechanics

Characterize MAP/CAST/TRIM/DATE and object mutations with nested paths, multiple matches, missing paths, and configured date fallbacks. Then extract common lookup/write-back mechanics only where reference mutation semantics are identical. MOVE and DROP alter paths differently and need not use the same helper.

Acceptance: ordered transforms produce the same payloads and configured fallback outcomes; developer handlers and package-owned transform definitions are not reparsed defensively.

The review's `composer lint` also reports two redundant typed-state checks in `ManageWebhooks.php`; remove those locally when that error display is touched, without adding a new validation layer.
