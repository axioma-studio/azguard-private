---
home: true
heroText: AzGuard
tagline: Code-first RBAC for Laravel. Roles as PHP classes. Permissions in Git.
actionText: Get Started →
actionLink: /guide/getting-started
features:
  - title: Code-first
    details: Roles are PHP classes. Permissions are PHP Enums. Everything lives in Git and gets code-reviewed.
  - title: Multi-panel
    details: Scope permissions by panel — admin, app, API. Each panel has its own isolated permission namespace.
  - title: PHP 8 Attributes
    details: "#[CheckPermission], #[GateAbility], #[SkipGuardCheck] — declarative access control in your code."
  - title: Doctor & Sync
    details: azguard:doctor finds orphaned policies and typos. azguard:sync-roles syncs PHP classes with DB.
footer: MIT License | Copyright © 2026 Axioma Studio
---

## Quick Start

```bash
composer require axioma-studio/azguard
php artisan vendor:publish --tag=az-guard-config
php artisan migrate
```

Add the trait to your User model:

```php
use AzGuard\Concerns\HasAzGuard;

class User extends Authenticatable
{
    use HasAzGuard;
}
```

→ [Full Getting Started guide](/guide/getting-started)
