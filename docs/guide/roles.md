# Работа с ролями

### Классовые роли (Code-first)
Создайте класс в папке `app/Guards/Admin/Roles/`:

```php
namespace App\Guards\Admin\Roles;

use AzGuard\Contracts\RoleInterface;

class AdminRole implements RoleInterface {
    public function getName(): string { return 'admin'; }
    public function getLevel(): int { return 100; }
    public function permissions(): array { return ['*']; }
}
```
