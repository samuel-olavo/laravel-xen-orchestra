# Changelog

## 1.1.0 — 2026-10-01

Targets Xen Orchestra 6.9.0 / REST API 0.40.2, revision `a2bd181d3528a61a45bbd4fe99686875fe8eebf8`.

### Added

- Backup archive live disk mount/unmount helpers, preserving mount results and encoding composite IDs.
- Backup repository space reclamation with optional `vmUuid`, `merge` and `remove` fields.
- Rolling pool update recovery inspection and finalization.
- Optional `bypassBackupCheck` for rolling updates/reboots and `acceptCurrentStateAsBaseline` for rolling updates; existing positional arguments remain compatible.
- REST API 0.40.2 route inventory: 264 non-deprecated controller routes, plus 12 deprecated aliases.
- Regression coverage for QCOW2 exports, download size headers and nine additional SSE collection types through the existing helpers.

### Compatibility and validation

Existing 1.0 calls remain compatible. New routes/options require server support. Tested using PHP 8.4.26 and a fake HTTP transport; no live-appliance integration test was performed. See [usage](docs/usage-1.1.md) and [coverage](docs/rest-api-0.40.md).

### Community

Thank you to the Vates and Xen Orchestra team for featuring this project in the [XO 6.9 announcement](https://xen-orchestra.com/blog/xen-orchestra-6-9/). This package was created to help PHP and Laravel developers and contribute to the community. We appreciate the recognition and everyone's feedback and contributions.

## 1.0.0 — 2026-09-10

Targets Xen Orchestra 6.8.0 / REST API 0.39.0, revision `faf6745471d7b2a00d774d98428873455e9539dc`.

### Added

- Fluent resources for all 32 current controller collections, including backup repositories, schedules, servers, hardware, VIFs, VBDs and ACL privileges.
- Host lifecycle and maintenance actions, including `scanPifs()`, and the IPMI plugin endpoint.
- Backup repository creation, updates, health checks, benchmarks and removal; schedule execution and backup/restore log reads.
- User/group and ACL role/privilege administration, membership management and authentication token operations.
- VDI/VBD/VIF creation and removal, disk migration, PBD plug/unplug and SR maintenance.
- SDN traffic rule creation, deletion and partial updates on networks and VIFs.
- Binary VM/VDI imports and exports, host log downloads and `Response::saveTo()` without overwriting existing files.
- SSE connections, event parsing, subscriptions and explicit unsubscribe/close operations.
- OpenAPI, GUI route and MCP status helpers; Markdown collection output.
- A pinned route inventory and regression tests accounting for every current controller operation.

### Fixed / migration from 0.x

- Empty JSON payloads are encoded as `{}` for XO object schemas.
- `Task::abort()` now returns the cancellation operation's Task; it no longer returns the original task. `abort(sync: true)` returns a completed cancellation Task.
- Snapshot/template/controller queries hydrate dedicated models and retain the correct collection path. These models do not expose unsupported VM power/update operations.
- IDs used to construct model paths are URL-encoded. Updates/deletes without an identity fail before sending a request.
- Binary uploads retain the caller's selected stream offset even when the transport rewinds the body.
- Synchronous action results, including newly created IDs, remain accessible through `Task::result()`.

### Validation and boundaries

Tested locally with PHP 8.4 / Laravel 13 and a fake transport. The CI matrix covers PHP 8.2–8.4 and Laravel 12/13; PHP 8.2 was not run locally. No live Xen Orchestra appliance was modified or used for integration testing.

The supported surface is the non-deprecated management REST API plus the documented IPMI/SDN plugin routes. Deprecated route aliases remain accessible through generic HTTP methods. This is not a JSON-RPC or MCP protocol client, and it does not implement server/UI features. See [the coverage table](docs/rest-api-0.39.md).

Composer obtains the published version from the Git tag `v1.0.0`; no `version` field is required in `composer.json`.
