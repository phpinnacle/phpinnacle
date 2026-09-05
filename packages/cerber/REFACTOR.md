# Refactor plan

Reviewed against the working tree on 2026-09-05. Preserve authorization decisions, permission names, model APIs, and panel registration behavior.

## 1. Priority: high — establish authorization and registration coverage

Existing tests cover password generation, permission-tab composition, and permission synchronization. They do not establish the behavior of `User::able()`, panel/tenant access, MFA persistence, or the conditional routes in `CerberPlugin::register()`.

- Before restructuring these areas, cover role permissions for two tenants, status-based panel access, tenant membership, and MFA save/load behavior.
- Exercise provider routes and the developer-login hook with enabled/disabled configuration and production mode. Check route names, authentication middleware, and registration order.
- Review repeated panel boot through `Cerberus::registerScoped()`, which attaches model callbacks each time. Change lifetime or registration mechanics only after reproducing duplicate callbacks or cross-panel state; static developer registrations alone do not prove a request-state leak.

## 2. Priority: medium — share permission checkbox setup

`Forms/PermissionTabs.php` repeats hidden labels, bulk toggling, and `adapt()` hydration in custom, resource, page, and widget lists. Extract only those defaults into a private builder; leave option sources, state paths, and the resource/custom four-column layout at their call sites.

Acceptance: retain tab merging, stable keys, translated labels, grant hydration, and page/widget layout. `Cerberus::getWidgets()` already returns class strings; remove the redundant `WidgetConfiguration` conversion in this consumer rather than widening that contract. The review's `composer lint` run reports this redundant check.

## Deferred

- Split `register()` in place only when the conditional registration tests make a focused change easier. Keep panel wiring in the plugin.
- Do not distribute `User` across single-use traits just to shorten the class. Its MFA state, relationships, identity, and permission reads belong to the model; no reusable concern or independent responsibility requiring extraction has been established.
