# AGENTS.md

This file provides guidance to Codex (Codex.ai/code) when working with code in this repository.

## What this plugin does

Jeedom plugin that supervises a [CrowdSec](https://www.crowdsec.net/) instance through its
local API (LAPI): active decision counts (local vs. community blocklist), the list of locally
blocked IPs, and manual ban/unban actions. Read-only monitoring uses a *bouncer* key; ban/unban
additionally requires a *machine* (watcher) account. See [docs/fr_FR/index.md](docs/fr_FR/index.md)
for the user-facing setup guide (bouncer/machine creation via `cscli`, LAPI URL, cron format).

No `.git` in this directory currently (see `bak/` note below if one existed before) — this is a
plain local copy, not yet a version-controlled clone of `jeedom_crowdsec`. Confirm with the user
before assuming any git workflow applies.

## Commands

No build/lint/test tooling in this plugin (no composer.json, no package.json, no tests/). Verify
changes with:

```bash
php -l core/class/crowdsec.class.php
php -l core/ajax/crowdsec.ajax.php
node --check desktop/js/crowdsec.js
```

For AJAX changes, call the endpoint directly (needs an authenticated admin session cookie):

```bash
curl -s 'http://<jeedom-host>/plugins/crowdsec/core/ajax/crowdsec.ajax.php' \
  -d 'action=testConnection&id=<eqLogic_id>' -b '<cookie>'
```

## Architecture

Everything lives in one `eqLogic` subclass, `crowdsec` (`core/class/crowdsec.class.php`), with
one `eqLogic` per CrowdSec instance/LAPI endpoint:

- **`lapiRequest()`** is the single HTTP entry point (cURL, TLS-verified, 5s connect/30s total
  timeout). `lapiGet()` wraps it with the bouncer key (`X-Api-Key` header, read-only). `lapiMachineRequest()`
  wraps it with a machine JWT obtained fresh on every call via `POST /v1/watchers/login` (no
  token caching — ban/unban are rare enough that this is intentional, not an oversight).
- **`refresh()`** (`GET /v1/decisions`) splits decisions into local vs. community using
  `COMMUNITY_ORIGINS = ['CAPI', 'lists']` — everything else (`crowdsec`, `cscli`, `appsec`...)
  counts as local — then updates the five `info` commands defined in `INFO_CMDS`.
- **`ban()` / `unban()`** validate the IP (`filter_var(..., FILTER_VALIDATE_IP)`) and, for ban,
  the duration format (`/^(\d+[hms])+$/`) before calling the machine-authenticated LAPI. `ban()`
  posts a full CrowdSec alert payload (the LAPI has no simpler "just add a decision" endpoint).
- **`postSave()`** (re)creates the fixed command set — 5 `info` + `refresh`/`ban`/`unban`
  `action` commands — idempotently (`createCmd()` no-ops if the logicalId already exists). This
  is also what `crowdsec_update()` in `plugin_info/install.php` relies on to add new commands
  introduced by a plugin update: it just re-`save()`s every existing `eqLogic`.
- **Cron**: `crowdsec::cron()` (registered by Jeedom core, not visible in this dir) iterates all
  `crowdsec` eqLogics and calls `refresh()` when each one's own `autorefresh` cron expression
  (per-eqLogic config, default `*/10 * * * *`) is due — polling frequency is configured per
  instrument, not globally.
- **Secrets**: `apiKey` and `machinePassword` (`SECRET_KEYS`) are the only two configuration
  keys ever encrypted (`encrypt()`/`decrypt()`, called by Jeedom core around save/load) —
  keep this list in sync if a new secret config key is added.

AJAX (`core/ajax/crowdsec.ajax.php`) only exposes `testConnection` and `refresh`; everything
else (ban/unban, config fields) goes through the normal Jeedom `eqLogicAttr`/`cmdAction`
save flow, not custom AJAX. Both actions require `isConnect('admin')` and resolve the target
eqLogic strictly by `getEqType_name() == 'crowdsec'` — preserve that check if adding new actions.

Desktop UI (`desktop/php/crowdsec.php` + `desktop/js/crowdsec.js`) is the standard Jeedom
thumbnail-card/tab layout with vanilla JS (no jQuery): `csCallAction()` drives the "Tester la
connexion"/"Actualiser maintenant" buttons via `domUtils.ajax`, and `addCmdToTable()` renders
the command list (info commands get a checkbox for "Historiser" only when numeric).

## Working conventions

See the parent-directory [`/var/www/html/plugins/AGENTS.md`](../AGENTS.md) for conventions
shared across all Jeedom plugins in this install (branching, proof-before-closing-a-task
protocol, and which skills to load for Jeedom core/UI/audit work).
