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

4. **Seed demo data** (tenants, roles, ticket examples)
   ```bash
   php artisan db:seed --class=RoleSeeder
   php artisan db:seed --class=TenantSeeder
   php artisan tickets:seed-defaults
   php artisan db:seed --class=DemoDataSeeder
   ```
   Use `php artisan tickets:seed-defaults {tenantSlugOrId}` to reseed lifecycle defaults for a specific tenant without touching others.

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
    "tag_ids": [1]
  }'
```

See [`OPENAPI.yaml`](OPENAPI.yaml) for request/response schemas and examples.

Ticket responses now include `status_definition`, `priority_definition`, and an `sla` snapshot describing breach/meet states for first-response and resolution targets. Ticket message endpoints support `GET /api/tickets/{ticket}/messages` and `POST /api/tickets/{ticket}/messages` for scoped collaboration with attachment ingestion, dedupe, and participant sync.

## Filament admin

Access the Filament panel at `/admin`. Ticket resources include SLA-aware fields, taxonomy pickers, and respect tenant/brand scopes automatically. A dedicated **Ticket Messages** resource surfaces conversation entries with per-tenant filters so agents can review or append collaboration notes without leaking across brands. Use the seeded credentials (e.g., `admin@acme.test` / `password`).

## Auditing & observability

Ticket and ticket-message lifecycle hooks emit structured JSON logs (`ticket.audit`, `ticket_message.audit`, `attachment.scan.dispatched`) and persist audit rows with non-sensitive metadata. SLA timestamps, collaboration events, and taxonomy updates all participate in the audit trail.

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
```

Running `php artisan test` executes the full suite, including API, policy, and Filament coverage.

## Environment reference

New environment keys:

- `TENANT_CONTEXT_HEADER` – header used to resolve the active tenant for scoped queries.
- `BRAND_CONTEXT_HEADER` – header used to resolve the active brand for scoped queries.

## Change management

Refer to [`CHANGELOG.md`](CHANGELOG.md) for a concise list of additions and behavioral changes made in this phase.
