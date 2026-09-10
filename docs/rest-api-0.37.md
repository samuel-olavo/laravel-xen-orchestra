# XO 6.7.0 / REST API 0.37.0

Reference: [commit 1a795970f9c60396967d9510e3d2a29b56f2da1d](https://github.com/vatesfr/xen-orchestra/commit/1a795970f9c60396967d9510e3d2a29b56f2da1d).
The commit marks the release in the changelog. Contracts were checked against the controller implementations at that revision, not the newer master branch.

## REST additions listed in the 6.7.0 release

| Endpoint / option | PHP interface | Behavior |
| --- | --- | --- |
| `POST /hosts/{id}/actions/disable`, `autoEnable` | `$host->disable(['autoEnable' => true])` | Task; evacuation options pass through |
| `PATCH /vdis/{id}` | `Xo::vdis()->find($id)->update($attributes)` | Same model; explicit `fetch()` to refresh |
| `POST /pools/{id}/actions/create_vm`, `high_availability` | `$pool->createVm($attributes)` | Existing pass-through supports the new field; regression tested |
| `GET /acl-roles/{id}/users` | `$role->users($fields, $query)` | Collection of User models |
| `GET /acl-roles/{id}/groups` | `$role->groups($fields, $query)` | Collection of Group models |
| `GET /groups/{id}/acl-roles` | `$group->aclRoles($fields, $query)` | Collection of AclRole models |
| `POST /srs` | `Xo::srs()->create($attributes)` | Partial StorageRepository; the server returns `{id}` |
| `POST /pools/{id}/actions/rolling_update`, `shutdownPinnedVms` | `$pool->rollingUpdate(shutdownPinnedVms: true)` | Task; existing positional `sync` remains compatible |
| `POST /pools/{id}/actions/rolling_reboot`, `shutdownPinnedVms` | `$pool->rollingReboot(shutdownPinnedVms: true)` | Task |
| `POST /pools/{id}/actions/create_bonded_network` | `$pool->createBondedNetwork($attributes)` | Task; XO enforces the new RBAC checks |
| `POST /pools/{id}/actions/create_internal_network` | `$pool->createInternalNetwork($attributes)` | Task; XO enforces the new RBAC checks |
| `POST /pools/{id}/actions/management_reconfigure` | `$pool->managementReconfigure($attributes)` | Task; route spelling verified in the controller |

Source: [release notes](https://github.com/vatesfr/xen-orchestra/blob/1a795970f9c60396967d9510e3d2a29b56f2da1d/CHANGELOG.md) and [REST controllers](https://github.com/vatesfr/xen-orchestra/tree/1a795970f9c60396967d9510e3d2a29b56f2da1d/%40xen-orchestra/rest-api/src).

## Additional gaps filled from the same revision

- `PATCH /vms/{id}` via `$vm->update($attributes)`.
- VM actions `clone`, `migrate` and `revert_snapshot` via `cloneVm()`, `migrate()` and `revertSnapshot()`.
- Host `enable` action.
- Fluent VDI, user, group and ACL role collections, with model resolution when following references.
- User groups and group users relations.
- VM disk listings now hydrate editable VirtualDisk models.
- Synchronous actions preserve the returned value in a completed Task rather than interpreting a newly created object's ID as a task ID.

## Compatibility and validation

New endpoints require a server version that exposes them and the appropriate XO privileges. The client does not emulate server-side features or automatically detect endpoint availability. Existing power actions and positional maintenance calls retain their signatures.

Tests use a fake PSR-18 transport to verify paths, verbs, payloads (including false/null values), response hydration, failures, and synchronous/asynchronous behavior. They do not replace integration tests against a running XO appliance.

This update covers the REST enhancements listed for 6.7.0 and the additional gaps above. It is not a claim of complete coverage of every REST endpoint added since 0.21.1. Binary VM/VDI transfers, SSE events, full identity/RBAC administration and other unmodelled endpoints remain outside these helpers; the generic HTTP methods remain available. Server fixes, UI changes, backup engine changes and OpenMetrics additions are implemented by upgrading XO itself.
