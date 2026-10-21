# Changelog

All notable changes to `phpinnacle/cerber` will be documented in this file.

## Unreleased

- Replace scope-based OAuth strategies with registered social-account contexts, session login, and a PKCE-protected JWT API.
- Isolate provider bindings by context and add context-aware account linking and unlinking.
- Allow active users to sign in through panel OAuth without an assigned role.
- Fix role registration for non-default tenants and transactional permission synchronization on the configured connection.
- Prevent duplicate tenant creation listeners during repeated panel boots.
- Simplify panel registration, permission forms, impersonation session cleanup, and user status changes.
- Update setup, authentication, tenancy, and API documentation; remove the obsolete refactor plan.
