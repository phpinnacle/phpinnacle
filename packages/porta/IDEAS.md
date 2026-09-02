# Ideas

Only small, additive features are listed here. Refactors and package-wide redesigns are intentionally excluded.

## 1. HMAC signature authentication

Verify signed webhook payloads with a configurable header, algorithm, timestamp tolerance, and constant-time signature comparison.

## 2. Delivery deduplication

Accept a provider event ID from a header or JSON path and return the original successful response when the same delivery is received again.

## 3. Payload retention and redaction

Redact configured secrets before persistence and prune completed payloads after a configurable retention period while retaining delivery status metadata.
