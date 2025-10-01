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
- Customer portal experience with knowledge base browse, branded ticket submission, and Filament brand theming. (#10)
- Two-factor authentication API with recovery codes and audit logging plus per-user IP restriction management. (#11)
- Channel ingestion endpoint with shared-secret header validation and structured logs. (#28)
- Ticket bulk action job and shared service powering API + Filament mass updates. (#28)
- Message visibility enforcement with viewer filtering and participant sanitisation. (#27)
- Collaboration flows ADR outlining adapter responsibilities and RBAC rules. (#29)
- Monitoring tokens, structured log channel, and `/api/health` dependency checks for observability pipelines. (#12)
- Channel adapter and ticket macro tables with factories, seeders, API + Filament resources, and OpenAPI coverage. (#25)
- Ticket watcher management (policy, API, audit logging) with assignment permission mapping and JSON payloads. (#20)
- Ticket schema & tenancy ADR documenting ERD, seeding runbooks, and watcher strategy. (#22)
- Email mailbox, inbound/outbound message, and attachment storage with tenant/brand scopes and factories. (#116)
- IMAP ingestion and SMTP delivery services with structured logging, attachment linking, and OpenAPI coverage. (#117)
- Email pipeline RBAC, policies, API + Filament resources, and viewer-safe message listings. (#118)

### Changed
- Ticket policy now honours Spatie permissions (`tickets.view`, `tickets.manage`).
- Demo seeders hydrate default ticket categories/tags and sample assignments.
- Ticket API create/update endpoints now pin tenant and brand IDs to the active context.
- Role seeding grants granular `ticket-messages.*` permissions across Admin/Agent/Viewer roles. (#24)
- Role seeding now provisions `tickets.assign`, `tickets.watchers.manage`, `channel-adapters.*`, and `ticket-macros.*` permissions mapped to Admin/Agent/Viewer roles. (#20, #25)
- Ticket API responses expose watcher collections alongside SLA snapshots; README + OpenAPI document the optional `watcher_ids` payloads. (#20)

### Security
- Tenant and brand headers are required to resolve scoped queries, preventing cross-tenant leakage.
- Ticket creation attempts for other tenants/brands are rejected with scoped 403 responses.
- Ticket message policies block viewers from posting replies and enforce scoped tenancy. (#24)
- Filament ticket table exposes assignment, status, and SLA bulk actions backed by the bulk action service. (#28)
- Demo seeders hydrate brand theming defaults and monitoring tokens for observability agents. (#10, #12)
- TOTP enrollment/confirmation routes honour tenant policies, redact secrets in audit logs, and enforce IP allowlists. (#11)
- Watcher synchronisation enforces tenant/brand membership and records `ticket.watchers.synced` audit entries with structured logs. (#20)
- Role seeding now provisions email pipeline permissions for Admin/Agent/Viewer roles and policies gate delivery/sync actions. (#118)
