# Ideas

Only small, additive features are listed here. Refactors and package-wide redesigns are intentionally excluded.

## 1. Atomic number reservation

Add a reservation method that increments a counter and returns the reserved value in one database operation, making pre-save number assignment safe under concurrency.

## 2. Additional periods

Provide daily, weekly, and fiscal-year counters using the existing global and scoped sequence patterns.

## 3. Counter inspection command

Add an Artisan command to list current counters and deliberately set the next value, with a dry-run option for operational recovery.
