# Collaboration Flows & Adapter Responsibilities

This document captures how ticket collaboration behaves across channels and the
expectations for adapter integrations.

## Channel Adapters

* **Endpoint:** `POST /api/tickets/{ticket}/ingest`
* **Auth:** Standard session auth plus the `X-Channel-Token` header. The value
  must match `CHANNEL_INGESTION_SECRET`.
* **Payload:** Mirrors `POST /api/tickets/{ticket}/messages` and supports
  attachments, participants, and dedupe metadata (`external_id`).
* **Idempotency:** External systems should reuse the same `external_id` when
  replaying messages to allow dedupe via the SHA-256 `dedupe_hash`.
* **Observability:** Successful ingests emit the `channel.message_ingested`
  structured log with tenant, brand, ticket, channel, and visibility data.

## Message Visibility & RBAC

* Agents and admins can create both public replies and internal notes.
* Contact-authored messages are forced to `public` visibility to prevent
  accidental leakage of private notes.
* Internal notes automatically exclude external participants when updating
  `ticket_participants` so requesters never receive a `last_message_id` that
  references private content.
* Viewers can list ticket messages but only receive `visibility = public`
  records. Policies enforce that internal notes are hidden from viewer role
  accounts across API and Filament.

## Bulk Collaboration Actions

* **Endpoint:** `POST /api/tickets/bulk-actions`
* **Actions:**
  * `assign` — reassign tickets to an agent.
  * `status` — transition status using the configured workflow definitions.
  * `sla` — adjust `first_response_due_at` or `resolution_due_at` windows.
* **Execution:** The request dispatches the `ProcessTicketBulkAction` job which
  normalises auth context, enforces `tickets.manage`, and returns structured
  results (`processed`, `skipped`, `errors`).
* **Filament UI:** Ticket admins gain new bulk actions for assignment, status,
  and SLA updates. These actions reuse the bulk action service to ensure the
  same tenancy, RBAC, and audit rules.

## Auditing & Logging

* Ticket bulk actions and ingestion both emit structured logs on the default
  stack channel. Sensitive message content is excluded from logs.
* Existing audit log observers capture ticket updates with the acting user ID
  supplied by the bulk action job.
