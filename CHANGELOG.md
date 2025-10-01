# Changelog

## [Unreleased]
### Added
- SLA-aware ticket schema with tenancy-aware scopes and soft deletes.
- Ticket taxonomy tables (categories, tags) with guarded pivot sync helpers and audit logging.
- REST + Filament workflows backed by Spatie policy enforcement, structured logs, and OpenAPI documentation.
- Ticket lifecycle reference tables (statuses, priorities, transitions) with a `tickets:seed-defaults` artisan command and demo seeding hooks.
- SLA observers emitting breach/recovery events, capturing sanitized audit metadata, and surfacing SLA snapshots via the API.
- Tenant isolation regression tests covering API CRUD, Filament listings, and policy enforcement.

### Changed
- Ticket policy now honours Spatie permissions (`tickets.view`, `tickets.manage`).
- Demo seeders hydrate default ticket categories/tags and sample assignments.
- Ticket API create/update endpoints now pin tenant and brand IDs to the active context.

### Security
- Tenant and brand headers are required to resolve scoped queries, preventing cross-tenant leakage.
- Ticket creation attempts for other tenants/brands are rejected with scoped 403 responses.
