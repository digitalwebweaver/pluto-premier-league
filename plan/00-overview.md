# Plan — 00 Overview

## What it is
Pluto Premier League: a Laravel + Vue (Inertia) web app for scoring LVB Pluto's inter-team meeting competition, with team self-reporting and LT approval before scores count.

## Modules
Authentication & accounts · Team management · Member roster · Meetings · Scoring engine (configurable) · Score entry (matchday scorecard) · Approval workflow · League table · Season summary · Reports & individual leaderboards (P2) · Notifications & announcements (P2) · Public display (P2).

## Stack
- **Laravel 11** (PHP 8.2+) — backend, routing, auth guards, policies, validation, Eloquent.
- **Vue 3 (Composition API) + Inertia.js** — server-driven SPA, no separate API layer.
- **Tailwind CSS + custom components** — no UI library; tokens in `tailwind.config.js`.
- **MariaDB** (local via phpMyAdmin; Laravel `mysql` driver) — relational store.
- **Vite** — asset build.
- Two auth guards: `team`, `lt`.

## Table of contents
| Doc | Topic |
|---|---|
| `01-architecture.md` | System architecture, request flow, Inertia structure |
| `02-data-model-schema.md` | Tables, relationships, key columns, indexes |
| `03-modules-features.md` | Module-by-module feature map → requirement IDs |
| `04-auth-user-management.md` | Guards, RBAC, account lifecycle, session strategy (priority foundation) |
| `05-scoring-engine-design.md` | How configurable rules + special math are implemented |
| `06-security.md` | Security controls mapped to NFR-SEC |
| `07-responsive-design.md` | Responsive/design-system application |
| `08-testing.md` | Test + smoke-test strategy |
| `09-deployment-infra.md` | Local dev + deployment approach |
| `10-phase-roadmap.md` | Phases, sub-phases, sequencing, exit criteria |

## Authoritative layers
- **Requirements:** `requirements/` (WHAT). Every acceptance criterion references a requirement ID.
- **Design:** `design.md` (+ `CLAUDE_DESIGN.md` for full rationale/sitemap).
- **Tracking:** `dev/` (PROGRESS, CURRENT-STATUS, SESSION-LOG).
