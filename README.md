# Ticketr Helpdesk Foundation

Ticketr is a multi-tenant helpdesk platform built on Laravel 12 and Filament 3. This phase introduces hardened ticket storage, taxonomy, and policy layers so every tenant receives isolated lifecycle tracking across channels.

## Getting Started

1. **Clone & install dependencies**
   ```bash
   composer install
   npm install
   ```
2. **Configure the application**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. **Run database migrations** (order matters for tenancy isolation)
   ```bash
   php artisan migrate
   ```
   The core sequence is:
   1. `0000_01_01_000100_create_tenants_table`
   2. `2024_01_01_000140_create_tickets_table`
   3. `2024_01_01_000210_create_ticket_categories_table`
   4. `2024_01_01_000220_create_ticket_tags_table`
   5. `2024_01_01_000230_create_ticket_category_ticket_table`
   6. `2024_01_01_000240_create_ticket_tag_ticket_table`
   7. `2024_01_01_000310_create_ticket_messages_table`
   8. `2024_01_01_000320_create_ticket_participants_table`
   9. Supporting tables (`audit_logs`, etc.)

4. **Seed demo data** (tenants, roles, ticket adapters/macros, ticket examples)
   ```bash
   php artisan db:seed --class=RoleSeeder
   php artisan db:seed --class=TenantSeeder
   php artisan tickets:seed-defaults
   php artisan db:seed --class=ChannelAdapterMacroSeeder
   php artisan db:seed --class=DemoDataSeeder
   ```
   Use `php artisan tickets:seed-defaults {tenantSlugOrId}` to reseed lifecycle defaults for a specific tenant without touching others. `ChannelAdapterMacroSeeder` can also be run independently to refresh seeded adapters/macros for new tenants.

5. **Run the dev stack**
   ```bash
   php artisan serve
   npm run dev
   ```

## Multi-tenant context

Requests resolve tenant and brand scopes from either authenticated users or explicit headers:

- `TENANT_CONTEXT_HEADER` (default `X-Tenant`)
- `BRAND_CONTEXT_HEADER` (default `X-Brand`)

When calling APIs or running background jobs, always provide these headers so global scopes filter records correctly.

## API usage

Authentication uses the standard web guard. Issue a session or `actingAs` token, then call the endpoints:

```bash
curl -X POST http://localhost/api/tickets \
  -H "X-Tenant: 1" \
  -H "X-Brand: 1" \
  -H "Accept: application/json" \
  -b cookie.txt \
  -d '{
    "brand_id": 1,
    "contact_id": 1,
    "company_id": 1,
    "subject": "Printer down",
    "priority": "urgent",
    "channel": "web",
    "category_ids": [1],
    "tag_ids": [1],
    "watcher_ids": [5]
  }'
```

See [`OPENAPI.yaml`](OPENAPI.yaml) for request/response schemas and examples.

Ticket responses now include watcher state alongside `status_definition`, `priority_definition`, and SLA snapshots so clients can render assignment + follower context. Ticket message endpoints support `GET /api/tickets/{ticket}/messages` and `POST /api/tickets/{ticket}/messages` for scoped collaboration with attachment ingestion, dedupe, and participant sync. Channel adapters can deliver replies via `POST /api/tickets/{ticket}/ingest` when providing the shared `X-Channel-Token` header (see [`docs/collaboration-flows.md`](docs/collaboration-flows.md)).

### Channel adapters & macros

Manage seeded channel connectors and reusable reply macros via API or Filament:

- `GET /api/channel-adapters` / `POST /api/channel-adapters`
- `GET /api/channel-adapters/{id}` / `PUT /api/channel-adapters/{id}` / `DELETE /api/channel-adapters/{id}`
- `GET /api/ticket-macros` / `POST /api/ticket-macros`
- `GET /api/ticket-macros/{id}` / `PUT /api/ticket-macros/{id}` / `DELETE /api/ticket-macros/{id}`

See the new ADR at [`docs/adr/2024-06-12-ticket-schema-and-tenancy.md`](docs/adr/2024-06-12-ticket-schema-and-tenancy.md) for schema diagrams, seeding strategy, and tenancy constraints.

### Email pipeline

Bidirectional email support is available for each tenant through secure mailbox configurations:

- `GET /api/email/mailboxes` / `POST /api/email/mailboxes` to manage IMAP/SMTP credentials scoped to the active tenant.
- `POST /api/email/mailboxes/{mailbox}/sync` to fetch messages from upstream providers and enqueue them for processing.
- `GET /api/email/inbound-messages` and `GET /api/email/outbound-messages` to review stored mail, along with `POST /api/email/outbound-messages/{id}/deliver` to trigger retries.

Mailboxes, inbound queues, and outbound messages are exposed in Filament under **Ticketing → Email Mailboxes / Inbound Emails / Outbound Emails**. Credentials are encrypted at rest, username hashes are returned for auditing, and attachments flow through the existing scanner before being linked to ticket messages.

## Filament admin

Access the Filament panel at `/admin`. Ticket resources include SLA-aware fields, taxonomy pickers, and respect tenant/brand scopes automatically. A dedicated **Ticket Messages** resource surfaces conversation entries with per-tenant filters so agents can review or append collaboration notes without leaking across brands. Use the seeded credentials (e.g., `admin@acme.test` / `password`).

## Customer portal

Every brand automatically exposes a customer-facing portal at `/portal/{brandSlug}`. The seeded `default` brand provides a quick starting point, while additional brands can tailor their look and feel via the new Filament **Brand** resource (configure primary/secondary/accent colours, logos, and portal domains). Customers can:

- Browse published knowledge base content filtered to their brand.
- Submit support requests via a guided form; submissions create tenant-scoped tickets, contacts, and public messages with full audit coverage.
- Track ticket status and review public agent responses without authenticating.

Portal pages inherit brand theming and structured HTML/CSS designed for easy white-labelling. Context is resolved with the `SetBrandFromRoute` middleware so all queries remain tenant-safe even for unauthenticated guests.

## Security controls

Two-factor authentication (TOTP) and IP restrictions safeguard agent sessions:

- `POST /api/security/two-factor` – initiate enrollment and receive the shared secret + recovery codes.
- `POST /api/security/two-factor/confirm` – verify a 6-digit TOTP code and activate protection.
- `DELETE /api/security/two-factor` – disable when needed.
- `PATCH /api/security/ip-restrictions` – manage per-user allow/deny lists for API access.

All actions emit audit log entries, redact sensitive metadata, and respect the `UserSecurityPolicy` (self-service plus tenant admins). Requests from blocked IPs are rejected before hitting controllers, and allowlists can constrain traffic to office networks.

## Auditing & observability

Ticket and ticket-message lifecycle hooks emit structured JSON logs (`ticket.audit`, `ticket_message.audit`, `attachment.scan.dispatched`) and persist audit rows with non-sensitive metadata. SLA timestamps, collaboration events, and taxonomy updates all participate in the audit trail.

The `/api/health` endpoint (guarded by `X-Monitoring-Token` and optional IP allowlists) returns database/Redis/queue/Scout readiness checks. Results stream to the dedicated `structured` log channel using newline-delimited JSON, simplifying ingestion by log forwarders.

SLA observers dispatch `ticket.sla.breached` / `ticket.sla.recovered` events, while the ticket message pipeline sanitises metadata, scans attachments, and redacts body content from logs.

Lifecycle defaults (names, SLAs, transitions) live in `config/ticketing.php`; override these arrays to customize tenant bootstrap behaviour.

## Testing

Focused test groups are provided per issue deliverable:

```bash
php artisan test --filter A1-DB-01
php artisan test --filter A1-DB-02
php artisan test --filter A1-MD-01
php artisan test --filter A1-SD-01
php artisan test --filter A1-OB-01
php artisan test --filter A1-TS-01
php artisan test --filter A2-DB-01
php artisan test --filter A2-MD-01
php artisan test --filter A2-OB-01
php artisan test --filter Issue-10
php artisan test --filter Issue-11
php artisan test --filter A2-RB-01
php artisan test --filter A2-TS-01
php artisan test --filter Issue-12
php artisan test --filter TKT-EMAIL-DB-01
php artisan test --filter TKT-EMAIL-MD-02
php artisan test --filter TKT-EMAIL-RB-03
```

Running `php artisan test` executes the full suite, including API, policy, and Filament coverage.

## Environment reference

New environment keys:

- `TENANT_CONTEXT_HEADER` – header used to resolve the active tenant for scoped queries.
- `BRAND_CONTEXT_HEADER` – header used to resolve the active brand for scoped queries.
- `CHANNEL_INGESTION_SECRET` – shared secret required by channel adapters posting to the ingestion endpoint.
- `LOG_STRUCTURED_LEVEL` – log level for newline-delimited JSON events persisted to `storage/logs/structured.log`.
- `MONITORING_TOKEN` – shared token for `/api/health` requests; hash is stored in `monitoring_tokens`.
- `MONITORING_ALLOWED_IPS` – comma-separated IPv4/IPv6 addresses permitted to call `/api/health`.

## Change management

Refer to [`CHANGELOG.md`](CHANGELOG.md) for a concise list of additions and behavioral changes made in this phase.
