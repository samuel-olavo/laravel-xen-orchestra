# REST API 0.39.0 coverage

Reference: [XO commit faf6745471d7b2a00d774d98428873455e9539dc](https://github.com/vatesfr/xen-orchestra/tree/faf6745471d7b2a00d774d98428873455e9539dc/%40xen-orchestra/rest-api/src).

Version 1.0.0 maps all 259 non-deprecated controller routes: 256 exercised by the route test matrix and three event routes covered by SSE tests. Seven IPMI/SDN plugin routes and the OpenAPI document have additional helpers. Twelve deprecated aliases use their current replacements. This counts routes, not independently validated payload schemas or live integration tests.

Core route facts are pinned in `tests/Fixtures/rest-api-0.39-routes.json`; the calls fixture is the reviewed PHP mapping. Tests check request paths, verbs, return categories and synchronous action completion. The server validates payload fields.

Local validation: PHP 8.4 / Laravel 13. PHP 8.2 was not run locally because the additional runtime download was blocked by automatic approval review. The CI matrix remains configured for PHP 8.2–8.4 and Laravel 12/13. No live-appliance integration test was performed.

## Controller routes

| Method | Path | PHP helper |
| --- | --- | --- |
| GET | `/acl-privileges` | `Xo::aclPrivileges()->get()` |
| POST | `/acl-privileges` | `Xo::aclPrivileges()->create()` |
| GET | `/acl-privileges/{id}` | `Xo::aclPrivileges()->find()` |
| DELETE | `/acl-privileges/{id}` | `AclPrivilege->delete()` |
| PATCH | `/acl-privileges/{id}` | `AclPrivilege->update()` |
| GET | `/acl-roles` | `Xo::aclRoles()->get()` |
| POST | `/acl-roles` | `Xo::aclRoles()->create()` |
| GET | `/acl-roles/{id}` | `Xo::aclRoles()->find()` |
| DELETE | `/acl-roles/{id}` | `AclRole->delete()` |
| PATCH | `/acl-roles/{id}` | `AclRole->update()` |
| POST | `/acl-roles/{id}/actions/copy` | `AclRole->copy()` |
| GET | `/acl-roles/{id}/privileges` | `AclRole->privileges()` |
| PUT | `/acl-roles/{id}/groups/{groupId}` | `AclRole->addGroup()` |
| DELETE | `/acl-roles/{id}/groups/{groupId}` | `AclRole->removeGroup()` |
| PUT | `/acl-roles/{id}/users/{userId}` | `AclRole->addUser()` |
| DELETE | `/acl-roles/{id}/users/{userId}` | `AclRole->removeUser()` |
| GET | `/acl-roles/{id}/users` | `AclRole->users()` |
| GET | `/acl-roles/{id}/groups` | `AclRole->groups()` |
| GET | `/alarms` | `Xo::alarms()->get()` |
| GET | `/alarms/{id}` | `Xo::alarms()->find()` |
| GET | `/backup-archives` | `Xo::backupArchives()->get()` |
| GET | `/backup-archives/{id}` | `Xo::backupArchives()->find()` |
| GET | `/backup-jobs` | `Xo::backupJobs()->get()` |
| GET | `/backup-jobs/{id}` | `Xo::backupJobs()->find()` |
| GET | `/backup-logs` | `Xo::backupLogs()->get()` |
| GET | `/backup-logs/{id}` | `Xo::backupLogs()->find()` |
| GET | `/backup-repositories` | `Xo::backupRepositories()->get()` |
| POST | `/backup-repositories` | `Xo::backupRepositories()->create()` |
| GET | `/backup-repositories/{id}` | `Xo::backupRepositories()->find()` |
| POST | `/backup-repositories/{id}/actions/forget` | `BackupRepository->forget()` |
| PATCH | `/backup-repositories/{id}` | `BackupRepository->update()` |
| GET | `/backup-repositories/{id}/health` | `BackupRepository->health()` |
| POST | `/backup-repositories/{id}/actions/benchmark` | `BackupRepository->benchmark()` |
| GET | `/groups` | `Xo::groups()->get()` |
| GET | `/groups/{id}` | `Xo::groups()->find()` |
| PATCH | `/groups/{id}` | `Group->update()` |
| POST | `/groups` | `Xo::groups()->create()` |
| DELETE | `/groups/{id}` | `Group->delete()` |
| DELETE | `/groups/{id}/users/{userId}` | `Group->removeUser()` |
| PUT | `/groups/{id}/users/{userId}` | `Group->addUser()` |
| GET | `/groups/{id}/users` | `Group->users()` |
| GET | `/groups/{id}/tasks` | `Group->tasks()` |
| GET | `/groups/{id}/acl-roles` | `Group->aclRoles()` |
| GET | `/hosts` | `Xo::hosts()->get()` |
| GET | `/hosts/{id}` | `Xo::hosts()->find()` |
| GET | `/hosts/{id}/stats` | `Host->stats()` |
| GET | `/hosts/{id}/audit.txt` | `Host->auditLog()` |
| GET | `/hosts/{id}/logs.tgz` | `Host->logs()` |
| GET | `/hosts/{id}/alarms` | `Host->alarms()` |
| GET | `/hosts/{id}/smt` | `Host->smt()` |
| GET | `/hosts/{id}/missing_patches` | `Host->missingPatches()` |
| GET | `/hosts/{id}/messages` | `Host->messages()` |
| GET | `/hosts/{id}/tasks` | `Host->tasks()` |
| PUT | `/hosts/{id}/tags/{tag}` | `Host->addTag()` |
| DELETE | `/hosts/{id}/tags/{tag}` | `Host->removeTag()` |
| POST | `/hosts/{id}/actions/management_reconfigure` | `Host->managementReconfigure()` |
| POST | `/hosts/{id}/actions/disable` | `Host->disable()` |
| POST | `/hosts/{id}/actions/enable` | `Host->enable()` |
| POST | `/hosts/{id}/actions/start` | `Host->start()` |
| POST | `/hosts/{id}/actions/clean_shutdown` | `Host->shutdown()` |
| POST | `/hosts/{id}/actions/clean_reboot` | `Host->reboot()` |
| POST | `/hosts/{id}/actions/smart_reboot` | `Host->smartReboot()` |
| POST | `/hosts/{id}/actions/restart_toolstack` | `Host->restartToolstack()` |
| POST | `/hosts/{id}/actions/emergency_shutdown` | `Host->emergencyShutdown()` |
| POST | `/hosts/{id}/actions/detach` | `Host->detach()` |
| POST | `/hosts/{id}/actions/forget` | `Host->forget()` |
| POST | `/hosts/{id}/actions/scan_pifs` | `Host->scanPifs()` |
| GET | `/mcp/status` | `Xo::mcpStatus()` |
| GET | `/messages` | `Xo::messages()->get()` |
| GET | `/messages/{id}` | `Xo::messages()->find()` |
| GET | `/networks` | `Xo::networks()->get()` |
| GET | `/networks/{id}` | `Xo::networks()->find()` |
| DELETE | `/networks/{id}` | `Network->delete()` |
| GET | `/networks/{id}/alarms` | `Network->alarms()` |
| GET | `/networks/{id}/messages` | `Network->messages()` |
| GET | `/networks/{id}/tasks` | `Network->tasks()` |
| PUT | `/networks/{id}/tags/{tag}` | `Network->addTag()` |
| DELETE | `/networks/{id}/tags/{tag}` | `Network->removeTag()` |
| GET | `/pbds` | `Xo::pbds()->get()` |
| GET | `/pbds/{id}` | `Xo::pbds()->find()` |
| POST | `/pbds/{id}/actions/plug` | `PhysicalBlockDevice->plug()` |
| POST | `/pbds/{id}/actions/unplug` | `PhysicalBlockDevice->unplug()` |
| GET | `/pcis` | `Xo::pcis()->get()` |
| GET | `/pcis/{id}` | `Xo::pcis()->find()` |
| GET | `/pgpus` | `Xo::pgpus()->get()` |
| GET | `/pgpus/{id}` | `Xo::pgpus()->find()` |
| GET | `/pifs` | `Xo::pifs()->get()` |
| GET | `/pifs/{id}` | `Xo::pifs()->find()` |
| GET | `/pifs/{id}/alarms` | `PhysicalInterface->alarms()` |
| GET | `/pifs/{id}/messages` | `PhysicalInterface->messages()` |
| GET | `/pifs/{id}/tasks` | `PhysicalInterface->tasks()` |
| GET | `/pools` | `Xo::pools()->get()` |
| GET | `/pools/{id}` | `Xo::pools()->find()` |
| POST | `/pools/{id}/actions/create_network` | `Pool->createNetwork()` |
| POST | `/pools/{id}/actions/create_bonded_network` | `Pool->createBondedNetwork()` |
| POST | `/pools/{id}/actions/create_internal_network` | `Pool->createInternalNetwork()` |
| POST | `/pools/{id}/actions/emergency_shutdown` | `Pool->emergencyShutdown()` |
| POST | `/pools/{id}/actions/rolling_reboot` | `Pool->rollingReboot()` |
| POST | `/pools/{id}/actions/rolling_update` | `Pool->rollingUpdate()` |
| POST | `/pools/{id}/vms` | `Pool->importVm()` |
| POST | `/pools/{id}/actions/create_vm` | `Pool->createVm()` |
| GET | `/pools/{id}/stats` | `Pool->stats()` |
| GET | `/pools/{id}/dashboard` | `Pool->dashboard()` |
| GET | `/pools/{id}/alarms` | `Pool->alarms()` |
| GET | `/pools/{id}/missing_patches` | `Pool->missingPatches()` |
| GET | `/pools/{id}/messages` | `Pool->messages()` |
| PUT | `/pools/{id}/tags/{tag}` | `Pool->addTag()` |
| DELETE | `/pools/{id}/tags/{tag}` | `Pool->removeTag()` |
| GET | `/pools/{id}/tasks` | `Pool->tasks()` |
| POST | `/pools/{id}/actions/management_reconfigure` | `Pool->managementReconfigure()` |
| POST | `/pools/{id}/actions/add_host` | `Pool->addHost()` |
| GET | `/proxies` | `Xo::proxies()->get()` |
| GET | `/proxies/{id}` | `Xo::proxies()->find()` |
| GET | `/restore-logs` | `Xo::restoreLogs()->get()` |
| GET | `/restore-logs/{id}` | `Xo::restoreLogs()->find()` |
| GET | `/schedules` | `Xo::schedules()->get()` |
| GET | `/schedules/{id}` | `Xo::schedules()->find()` |
| POST | `/schedules/{id}/actions/run` | `Schedule->run()` |
| GET | `/servers` | `Xo::servers()->get()` |
| GET | `/servers/{id}` | `Xo::servers()->find()` |
| DELETE | `/servers/{id}` | `Server->delete()` |
| POST | `/servers` | `Xo::servers()->create()` |
| POST | `/servers/{id}/actions/connect` | `Server->connect()` |
| POST | `/servers/{id}/actions/disconnect` | `Server->disconnect()` |
| GET | `/servers/{id}/tasks` | `Server->tasks()` |
| GET | `/sms` | `Xo::sms()->get()` |
| GET | `/sms/{id}` | `Xo::sms()->find()` |
| GET | `/srs` | `Xo::srs()->get()` |
| GET | `/srs/{id}` | `Xo::srs()->find()` |
| POST | `/srs` | `Xo::srs()->create()` |
| GET | `/srs/{id}/alarms` | `StorageRepository->alarms()` |
| POST | `/srs/{id}/vdis` | `StorageRepository->importVdi()` |
| GET | `/srs/{id}/messages` | `StorageRepository->messages()` |
| GET | `/srs/{id}/tasks` | `StorageRepository->tasks()` |
| PUT | `/srs/{id}/tags/{tag}` | `StorageRepository->addTag()` |
| DELETE | `/srs/{id}/tags/{tag}` | `StorageRepository->removeTag()` |
| POST | `/srs/{id}/actions/reclaim_space` | `StorageRepository->reclaimSpace()` |
| POST | `/srs/{id}/actions/scan` | `StorageRepository->scan()` |
| POST | `/srs/{id}/actions/forget` | `StorageRepository->forget()` |
| DELETE | `/srs/{id}` | `StorageRepository->delete()` |
| GET | `/tasks` | `Xo::tasks()->get()` |
| GET | `/tasks/{id}` | `Xo::tasks()->find()` |
| DELETE | `/tasks` | `Xo::tasks()->purge()` |
| DELETE | `/tasks/{id}` | `Task->forget()` |
| POST | `/tasks/{id}/actions/abort` | `Task->abort()` |
| GET | `/users` | `Xo::users()->get()` |
| GET | `/users/{id}` | `Xo::users()->find()` |
| PATCH | `/users/{id}` | `User->update()` |
| POST | `/users` | `Xo::users()->create()` |
| DELETE | `/users/{id}` | `User->delete()` |
| GET | `/users/{id}/groups` | `User->groups()` |
| GET | `/users/{id}/authentication_tokens` | `User->authenticationTokens()` |
| GET | `/users/{id}/tasks` | `User->tasks()` |
| POST | `/users/{id}/authentication_tokens` | `User->createAuthenticationToken()` |
| GET | `/users/{id}/acl-privileges` | `User->privileges()` |
| GET | `/vbds` | `Xo::vbds()->get()` |
| GET | `/vbds/{id}` | `Xo::vbds()->find()` |
| POST | `/vbds` | `Xo::vbds()->create()` |
| DELETE | `/vbds/{id}` | `VirtualBlockDevice->delete()` |
| GET | `/vbds/{id}/alarms` | `VirtualBlockDevice->alarms()` |
| GET | `/vbds/{id}/messages` | `VirtualBlockDevice->messages()` |
| GET | `/vbds/{id}/tasks` | `VirtualBlockDevice->tasks()` |
| POST | `/vbds/{id}/actions/connect` | `VirtualBlockDevice->connect()` |
| POST | `/vbds/{id}/actions/disconnect` | `VirtualBlockDevice->disconnect()` |
| GET | `/vdi-snapshots` | `Xo::vdiSnapshots()->get()` |
| GET | `/vdi-snapshots/{id}.{format}` | `VirtualDiskSnapshot->export()` |
| GET | `/vdi-snapshots/{id}` | `Xo::vdiSnapshots()->find()` |
| GET | `/vdi-snapshots/{id}/alarms` | `VirtualDiskSnapshot->alarms()` |
| DELETE | `/vdi-snapshots/{id}` | `VirtualDiskSnapshot->delete()` |
| GET | `/vdi-snapshots/{id}/messages` | `VirtualDiskSnapshot->messages()` |
| GET | `/vdi-snapshots/{id}/tasks` | `VirtualDiskSnapshot->tasks()` |
| PUT | `/vdi-snapshots/{id}/tags/{tag}` | `VirtualDiskSnapshot->addTag()` |
| DELETE | `/vdi-snapshots/{id}/tags/{tag}` | `VirtualDiskSnapshot->removeTag()` |
| GET | `/vdis` | `Xo::vdis()->get()` |
| GET | `/vdis/{id}.{format}` | `VirtualDisk->export()` |
| PUT | `/vdis/{id}.{format}` | `VirtualDisk->importContent()` |
| GET | `/vdis/{id}` | `Xo::vdis()->find()` |
| PATCH | `/vdis/{id}` | `VirtualDisk->update()` |
| GET | `/vdis/{id}/alarms` | `VirtualDisk->alarms()` |
| POST | `/vdis` | `Xo::vdis()->create()` |
| DELETE | `/vdis/{id}` | `VirtualDisk->delete()` |
| GET | `/vdis/{id}/messages` | `VirtualDisk->messages()` |
| GET | `/vdis/{id}/tasks` | `VirtualDisk->tasks()` |
| POST | `/vdis/{id}/actions/migrate` | `VirtualDisk->migrate()` |
| PUT | `/vdis/{id}/tags/{tag}` | `VirtualDisk->addTag()` |
| DELETE | `/vdis/{id}/tags/{tag}` | `VirtualDisk->removeTag()` |
| GET | `/vifs` | `Xo::vifs()->get()` |
| GET | `/vifs/{id}` | `Xo::vifs()->find()` |
| PATCH | `/vifs/{id}` | `VirtualInterface->update()` |
| GET | `/vifs/{id}/alarms` | `VirtualInterface->alarms()` |
| GET | `/vifs/{id}/messages` | `VirtualInterface->messages()` |
| GET | `/vifs/{id}/tasks` | `VirtualInterface->tasks()` |
| POST | `/vifs` | `Xo::vifs()->create()` |
| DELETE | `/vifs/{id}` | `VirtualInterface->delete()` |
| POST | `/vifs/{id}/actions/connect` | `VirtualInterface->connect()` |
| POST | `/vifs/{id}/actions/disconnect` | `VirtualInterface->disconnect()` |
| GET | `/vm-controllers` | `Xo::vmControllers()->get()` |
| GET | `/vm-controllers/{id}` | `Xo::vmControllers()->find()` |
| GET | `/vm-controllers/{id}/alarms` | `VirtualMachineController->alarms()` |
| GET | `/vm-controllers/{id}/vdis` | `VirtualMachineController->disks()` |
| GET | `/vm-controllers/{id}/messages` | `VirtualMachineController->messages()` |
| GET | `/vm-controllers/{id}/tasks` | `VirtualMachineController->tasks()` |
| PUT | `/vm-controllers/{id}/tags/{tag}` | `VirtualMachineController->addTag()` |
| DELETE | `/vm-controllers/{id}/tags/{tag}` | `VirtualMachineController->removeTag()` |
| GET | `/vm-snapshots` | `Xo::vmSnapshots()->get()` |
| GET | `/vm-snapshots/{id}.{format}` | `VirtualMachineSnapshot->export()` |
| GET | `/vm-snapshots/{id}` | `Xo::vmSnapshots()->find()` |
| DELETE | `/vm-snapshots/{id}` | `VirtualMachineSnapshot->delete()` |
| GET | `/vm-snapshots/{id}/alarms` | `VirtualMachineSnapshot->alarms()` |
| GET | `/vm-snapshots/{id}/vdis` | `VirtualMachineSnapshot->disks()` |
| GET | `/vm-snapshots/{id}/messages` | `VirtualMachineSnapshot->messages()` |
| GET | `/vm-snapshots/{id}/tasks` | `VirtualMachineSnapshot->tasks()` |
| PUT | `/vm-snapshots/{id}/tags/{tag}` | `VirtualMachineSnapshot->addTag()` |
| DELETE | `/vm-snapshots/{id}/tags/{tag}` | `VirtualMachineSnapshot->removeTag()` |
| GET | `/vm-templates` | `Xo::vmTemplates()->get()` |
| GET | `/vm-templates/{id}.{format}` | `VirtualMachineTemplate->export()` |
| GET | `/vm-templates/{id}` | `Xo::vmTemplates()->find()` |
| DELETE | `/vm-templates/{id}` | `VirtualMachineTemplate->delete()` |
| GET | `/vm-templates/{id}/alarms` | `VirtualMachineTemplate->alarms()` |
| GET | `/vm-templates/{id}/vdis` | `VirtualMachineTemplate->disks()` |
| GET | `/vm-templates/{id}/messages` | `VirtualMachineTemplate->messages()` |
| GET | `/vm-templates/{id}/tasks` | `VirtualMachineTemplate->tasks()` |
| PUT | `/vm-templates/{id}/tags/{tag}` | `VirtualMachineTemplate->addTag()` |
| DELETE | `/vm-templates/{id}/tags/{tag}` | `VirtualMachineTemplate->removeTag()` |
| GET | `/vms` | `Xo::vms()->get()` |
| GET | `/vms/{id}.{format}` | `VirtualMachine->export()` |
| GET | `/vms/{id}` | `Xo::vms()->find()` |
| PATCH | `/vms/{id}` | `VirtualMachine->update()` |
| DELETE | `/vms/{id}` | `VirtualMachine->delete()` |
| GET | `/vms/{id}/stats` | `VirtualMachine->stats()` |
| PUT | `/vms/{id}/stats/data_source/{data_source}` | `VirtualMachine->enableDataSource()` |
| DELETE | `/vms/{id}/stats/data_source/{data_source}` | `VirtualMachine->disableDataSource()` |
| POST | `/vms/{id}/actions/start` | `VirtualMachine->start()` |
| POST | `/vms/{id}/actions/clean_shutdown` | `VirtualMachine->shutdown()` |
| POST | `/vms/{id}/actions/clean_reboot` | `VirtualMachine->reboot()` |
| POST | `/vms/{id}/actions/hard_shutdown` | `VirtualMachine->shutdown()` |
| POST | `/vms/{id}/actions/hard_reboot` | `VirtualMachine->reboot()` |
| POST | `/vms/{id}/actions/pause` | `VirtualMachine->pause()` |
| POST | `/vms/{id}/actions/suspend` | `VirtualMachine->suspend()` |
| POST | `/vms/{id}/actions/resume` | `VirtualMachine->resume()` |
| POST | `/vms/{id}/actions/unpause` | `VirtualMachine->unpause()` |
| POST | `/vms/{id}/actions/revert_snapshot` | `VirtualMachine->revertSnapshot()` |
| POST | `/vms/{id}/actions/snapshot` | `VirtualMachine->snapshot()` |
| POST | `/vms/{id}/actions/clone` | `VirtualMachine->cloneVm()` |
| GET | `/vms/{id}/alarms` | `VirtualMachine->alarms()` |
| GET | `/vms/{id}/vdis` | `VirtualMachine->disks()` |
| GET | `/vms/{id}/backup-jobs` | `VirtualMachine->backupJobs()` |
| GET | `/vms/{id}/messages` | `VirtualMachine->messages()` |
| GET | `/vms/{id}/tasks` | `VirtualMachine->tasks()` |
| PUT | `/vms/{id}/tags/{tag}` | `VirtualMachine->addTag()` |
| DELETE | `/vms/{id}/tags/{tag}` | `VirtualMachine->removeTag()` |
| GET | `/vms/{id}/dashboard` | `VirtualMachine->dashboard()` |
| POST | `/vms/{id}/actions/migrate` | `VirtualMachine->migrate()` |
| GET | `/dashboard` | `Xo::dashboard()` |
| GET | `/ping` | `Xo::ping()` |
| GET | `/gui-routes` | `Xo::guiRoutes()` |
| GET | `/events` | `Xo::events()->open()` |
| POST | `/events/{id}/subscriptions` | `Xo::events()->subscribe()` |
| DELETE | `/events/{id}/subscriptions/{subscriptionId}` | `Xo::events()->unsubscribe()` |

## Plugins and metadata

| Method | Path | PHP helper |
| --- | --- | --- |
| GET | `/plugins/ipmi-sensors/hosts/{id}/ipmi` | `Host->ipmi()` |
| GET | `/docs/swagger.json` | `Xo::openApi()` |
| POST | `/plugins/sdn-controller/networks/{id}/actions/add_traffic_rule` | `Network->addTrafficRule()` |
| POST | `/plugins/sdn-controller/networks/{id}/actions/delete_traffic_rule` | `Network->deleteTrafficRule()` |
| POST | `/plugins/sdn-controller/networks/{id}/actions/update_traffic_rule` | `Network->updateTrafficRule()` |
| POST | `/plugins/sdn-controller/vifs/{id}/actions/add_traffic_rule` | `VirtualInterface->addTrafficRule()` |
| POST | `/plugins/sdn-controller/vifs/{id}/actions/delete_traffic_rule` | `VirtualInterface->deleteTrafficRule()` |
| POST | `/plugins/sdn-controller/vifs/{id}/actions/update_traffic_rule` | `VirtualInterface->updateTrafficRule()` |

## Deprecated aliases

These have supported modern replacements; no new dedicated wrappers are added for retired names. Generic `get()`/`post()` remain available when needed.

- `GET /backup/jobs/vm`
- `GET /backup/jobs/{id}`
- `GET /backup/jobs/vm/{id}`
- `GET /backup/jobs/metadata`
- `GET /backup/jobs/metadata/{id}`
- `GET /backup/jobs/mirror`
- `GET /backup/jobs/mirror/{id}`
- `GET /backup/logs`
- `GET /backup/logs/{id}`
- `GET /restore/logs`
- `GET /restore/logs/{id}`
- `POST /users/authentication_tokens`

The backup aliases map to `/backup-jobs` and `/backup-logs`; restore aliases map to `/restore-logs`; token creation maps to `/users/{id}/authentication_tokens`.

## Scope

This package covers the pinned management REST surface and the plugin routes above. It does not implement JSON-RPC features, a complete MCP protocol client, arbitrary external plugins, the Swagger HTML UI, or XO server/UI behavior. Plugin availability and privileges are enforced by the appliance. Streaming does not automatically reconnect, resume transfers or recreate subscriptions.
