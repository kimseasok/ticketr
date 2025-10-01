# ADR: Ticket Schema & Tenancy Strategy

## Status
Accepted – 2024-06-12

## Context
Ticket operations now span assignment, watcher management, seeded channel adapters, and reusable reply macros. We need to document how the relational model enforces tenant/brand isolation, what defaults are seeded, and how observers keep audit trails and SLA metadata consistent.

## Decision
* Extend the ticket domain with channel adapter (`channel_adapters`) and macro (`ticket_macros`) tables keyed by `tenant_id` with optional `brand_id`. Both tables use composite uniqueness (`tenant_id`, `slug`) and JSON columns for connector configuration and macro metadata.
* Enforce RBAC by mapping new permissions – `tickets.assign`, `tickets.watchers.manage`, `channel-adapters.manage/view`, and `ticket-macros.manage/view` – to Admin/Agent/Viewer roles via `RoleSeeder`.
* Expose dedicated API + Filament resources for channel adapters and macros. Controllers wrap every mutation in tenant/brand scope checks and emit structured logs plus `audit_logs` entries (`channel_adapter.*`, `ticket_macro.*`).
* Extend `TicketPolicy` to gate assignment and watcher updates. `Ticket::syncWatchers()` sanitises watcher IDs to the active tenant/brand, persists participants with `role = watcher`, and writes `ticket.watchers.synced` audit logs.
* Ticket responses include `watchers[]` payloads with muted/last-seen data, ensuring clients have an immutable view of watcher state without exposing contact PII.

## Schema Summary
```
channel_adapters
  id PK
  tenant_id FK -> tenants
  brand_id FK -> brands (nullable)
  name, slug (unique with tenant), channel ENUM(email|web|chat|phone)
  provider VARCHAR(120)
  configuration JSON, metadata JSON
  is_active BOOLEAN, last_synced_at TIMESTAMP

ticket_macros
  id PK
  tenant_id FK -> tenants
  brand_id FK -> brands (nullable)
  name, slug (unique with tenant)
  description TEXT, body TEXT
  visibility ENUM(tenant|brand|private)
  is_shared BOOLEAN, metadata JSON
```
`ticket_participants` already stores watcher rows with `role = watcher`; no schema change was required, but indexes now underpin watcher queries in `Ticket::syncWatchers()`.

## Workflow Defaults & Observers
* `ChannelAdapterMacroSeeder` hydrates baseline adapters (`email-gateway`, `chat-widget`, `voice-bridge`) and macros (`acknowledge-ticket`, `escalate-tier-2`, `close-with-csat`) for every tenant/brand. Seed data carries a `metadata.seeded` flag to differentiate custom records.
* Audit logging hooks now cover adapter/macro CRUD and watcher synchronisation. Existing ticket observers continue to capture SLA metadata; watcher updates append structured JSON (`ticket.watchers_synced`) with actor, tenant, brand, and watcher IDs.
* Private macros automatically capture `metadata.owner_id` for RBAC enforcement in `TicketMacroPolicy`.

## Runbook Updates
1. **Migrations** – Run `php artisan migrate` after pulling the release. Two new migrations (`2024_01_03_000500_create_channel_adapters_table`, `2024_01_03_000510_create_ticket_macros_table`) introduce the supporting tables.
2. **Seeding** – Execute `php artisan db:seed` (or specifically `php artisan db:seed --class=ChannelAdapterMacroSeeder`) to provision default connectors and macros per tenant/brand. Existing tenants receive idempotent updates via `updateOrCreate`.
3. **Permissions Cache** – Flush Spatie caches (`php artisan permission:cache-reset` or `app(PermissionRegistrar::class)->forgetCachedPermissions()`) so new permissions take effect.
4. **Docs** – API clients should refresh against `OPENAPI.yaml` to adopt `watcher_ids` on ticket create/update and the new `/api/channel-adapters` + `/api/ticket-macros` resources.

## Consequences
* Tenants gain turnkey channel ingestion defaults and reusable macros without manual SQL, accelerating onboarding.
* Watcher management now honours RBAC and emits auditable events, preventing silent participant drift across brands.
* Additional seeds increase database footprint slightly but remain idempotent and scoped by tenant.
* API clients must update to supply `watcher_ids` (optional) and handle watcher arrays in ticket payloads.
