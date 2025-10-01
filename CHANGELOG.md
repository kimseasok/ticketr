# Changelog

## [Unreleased]
### Added
- SLA-aware ticket schema with tenancy-aware scopes and soft deletes.
- Ticket taxonomy tables (categories, tags) with guarded pivot sync helpers and audit logging.
- REST + Filament workflows backed by Spatie policy enforcement, structured logs, and OpenAPI documentation.
- Ticket lifecycle reference tables (statuses, priorities, transitions) with a `tickets:seed-defaults` artisan command and demo seeding hooks.
- SLA observers emitting breach/recovery events, capturing sanitized audit metadata, and surfacing SLA snapshots via the API.
- Tenant isolation regression tests covering API CRUD, Filament listings, and policy enforcement.
- Ticket message + participant schema with tenant indexes and dedupe hashes for channel ingestion. (#23)
- Ticket message service, API, and Filament resource with RBAC enforcement and structured logging. (#24)
- Attachment scanning & audit observers covering collaboration lifecycle. (#26)

### Changed
- Ticket policy now honours Spatie permissions (`tickets.view`, `tickets.manage`).
- Demo seeders hydrate default ticket categories/tags and sample assignments.
- Ticket API create/update endpoints now pin tenant and brand IDs to the active context.
- Role seeding grants granular `ticket-messages.*` permissions across Admin/Agent/Viewer roles. (#24)

### Security
- Tenant and brand headers are required to resolve scoped queries, preventing cross-tenant leakage.
- Ticket creation attempts for other tenants/brands are rejected with scoped 403 responses.
- Ticket message policies block viewers from posting replies and enforce scoped tenancy. (#24)
