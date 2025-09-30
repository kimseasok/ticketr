# Changelog

## [Unreleased]
### Added
- SLA-aware ticket schema with tenancy-aware scopes and soft deletes.
- Ticket taxonomy tables (categories, tags) with guarded pivot sync helpers and audit logging.
- REST + Filament workflows backed by Spatie policy enforcement, structured logs, and OpenAPI documentation.

### Changed
- Ticket policy now honours Spatie permissions (`tickets.view`, `tickets.manage`).
- Demo seeders hydrate default ticket categories/tags and sample assignments.

### Security
- Tenant and brand headers are required to resolve scoped queries, preventing cross-tenant leakage.
