# Гибридный реестр прав: code-first + контролируемые DB-узлы

План на будущую реализацию в AzGuard. Документ фиксирует концепцию универсальности без «полной свободы БД», сравнение с похожими решениями и предлагаемую структуру классов.

---

## 1. Контекст и цель

### 1.1. Зачем AzGuard

Целевая модель — **не** «всё в базе, как в Spatie», и **не** «только хардкод в Policy без схемы», а промежуточный слой:

| Требование | Смысл |
|------------|--------|
| **Источник истины в коде** | Каждое permission объявлено в PHP (enum / каталог), привязано к политике и проверяется статически (IDE, тесты, `azguard:doctor`). |
| **Жёсткая привязка в рантайме** | `hasAzPermission('app.documents.view')` ссылается на ключ из каталога; «мертвые» строки в БД без кода недопустимы. |
| **Гибкость там, где нужна** | Назначение ролей пользователям, уровни, scope по сущностям — в БД; опционально — **дополнительные** grants/overrides, но только в рамках зарегистрированного каталога или явных dynamic-паттернов. |
| **Панели (guards)** | Изоляция `admin.*` / `app.*`, отдельные деревья Permissions/Policies/Roles. |

### 1.2. Чего избегаем

- Админка создаёт permission `foo.bar` → в Gate он есть, в коде нигде не используется.
- Свободный CRUD таблицы `permissions` без связи с политиками.
- Дублирование бизнес-правил: и в Policy, и в «abilities» для фронта (уже зафиксировано: Abilities — проекция, не второй источник).

---

## 2. Текущее состояние AzGuard (baseline)

Уже реализовано и задаёт направление гибрида:

```
Панель (Panel)
  ├── Permissions/*Permission.php   → backed enum, короткий ключ
  ├── Policies/*Policy.php          → #[GateAbility(permission: EnumCase)]
  ├── Roles/*Role.php               → permissions(): list<resolved string>
  └── (опц.) Scopes, Abilities

БД:
  roles (name, class_name, level)
  model_has_roles
  model_has_scopes (scope_class + morph entity)
```

**Поток:** роль в БД → `class_name` → инстанс `RoleInterface` → `permissions()` → `hasAzPermission` → Policy + доменные правила.

**GuardDoctor** уже проверяет code-first контракт:

- каждый case enum имеет `#[GateAbility]` в политике;
- каждая строка в `Role::permissions()` ∈ известных abilities панели;
- нет дубликатов resolved ability.

**Пробел:** БД хранит только **привязку пользователя к роли**, не хранит «лишние» permissions и не поддерживает легитимные сценарии «добавить право из UI без нового PHP-класса роли» (кастомная роль, временный grant, плагин).

---

## 3. Сравнительный анализ похожих пакетов

### 3.1. Spatie `laravel-permission`

| Аспект | Поведение |
|--------|-----------|
| Модель | `Role`, `Permission` — полноценные Eloquent-модели; pivot `role_has_permissions`, `model_has_permissions`. |
| Источник истины | **БД**; имена permission — строки; при boot регистрация на Gate из кэша (`spatie.permission.cache`). |
| Плюсы | Зрелость, middleware, teams/guards, UI-админки (Filament plugins). |
| Минусы для нашей концепции | Легко создать permission, которого нет в коде; рефакторинг rename permission — миграции данных; слабая связь с Policy method. |
| Урок для AzGuard | Взять: кэш агрегата прав, события сброса кэша, `givePermissionTo` ergonomics. **Не брать:** таблицу `permissions` как первичный реестр. |

### 3.2. Silber `bouncer`

| Аспект | Поведение |
|--------|-----------|
| Модель | `abilities` + `permissions` (entity-scoped) + `roles` + `assigned_roles`. |
| Источник истины | **БД + runtime** (`Bouncer::allow($user)->to('edit', $post)`); код приложения может иметь приоритет. |
| Плюсы | Ability на экземпляр модели, forbidden flag, scope; хорошо для «только свои записи». |
| Минусы | Динамика без жёсткого каталога; сложнее статический анализ. |
| Урок для AzGuard | Паттерн **entity-scoped grant** вынести в `model_has_scopes` / отдельный `Grant` с `scope_entity`; не смешивать с «именем permission». |

### 3.3. Laravel native (Policy + Gate)

| Аспект | Поведение |
|--------|-----------|
| Модель | Нет RBAC из коробки; `@can` → Policy. |
| Плюсы | Политики = бизнес-правила; типизация через методы. |
| Минусы | Нет ролей/permission catalog; каждый проект изобретает своё. |
| Урок для AzGuard | AzGuard — **надстройка** над Gate, не замена Policy; wildcard только через `Gate::before` / `*`. |

### 3.4. Casbin / Oso / внешние policy engines

| Аспект | Поведение |
|--------|-----------|
| Модель | Политики в файле/БД (RBAC/ABAC), отдельный движок. |
| Плюсы | Мощная ABAC, внешнее управление. |
| Минусы | Другой язык правил, разрыв с PHP enum и Laravel Policy. |
| Урок для AzGuard | Для CRM достаточно RBAC + scoped queries; полноценный ABAC — только если появится отдельный модуль (не в v1 гибрида). |

### 3.5. Filament Shield / UI поверх Spatie

| Аспект | Поведение |
|--------|-----------|
| Модель | Генерация permission из Resource (`view_any`, `update`, …). |
| Плюсы | Быстрый старт админки. |
| Минусы | Привязка к Filament Resource, не к доменным enum панели app. |
| Урок для AzGuard | Filament-пакет AzGuard — **читатель каталога** + назначение ролей, не генератор произвольных строк. |

### 3.6. Сводная таблица позиционирования

| | Spatie | Bouncer | AzGuard (цель) |
|---|--------|---------|----------------|
| Каталог permissions | БД | БД (abilities) | **PHP registry** |
| Назначение пользователю | БД | БД | БД (роли + опц. grants) |
| Проверка в коде | `can('string')` | `can('string')` | enum → resolved + Policy |
| Orphan permissions | Возможны | Возможны | **Ошибка doctor / CI** |
| Scoped access | Через кастом | Встроено | `ScopeInterface` + Policy |

---

## 4. Целевая модель: Hybrid Permission Registry

### 4.1. Три слоя (не смешивать)

```text
┌─────────────────────────────────────────────────────────────┐
│  L1: Permission Catalog (code, immutable at runtime)        │
│      enum, metadata, группы, dynamic-шаблоны (объявлены)    │
└───────────────────────────┬─────────────────────────────────┘
                            │ validates keys
┌───────────────────────────▼─────────────────────────────────┐
│  L2: Grant Sources (composite)                              │
│      • ClassRoleGrantSource   ← Role PHP class               │
│      • DatabaseGrantSource    ← pivot, только ключи из L1    │
│      • (опц.) PluginGrantSource ← провайдеры модулей           │
└───────────────────────────┬─────────────────────────────────┘
                            │ union → effective permissions
┌───────────────────────────▼─────────────────────────────────┐
│  L3: Authorization (Laravel Gate)                           │
│      #[GateAbility] → Policy → hasAzPermission + domain      │
└─────────────────────────────────────────────────────────────┘
```

**Правило:** L2 никогда не вводит ключ, которого нет в L1 (кроме явно объявленных dynamic-шаблонов с валидатором).

### 4.2. Типы узлов каталога (L1)

| Тип | Описание | Пример |
|-----|----------|--------|
| `StaticPermission` | Backed enum case | `DocumentsPermission::View` → `app.documents.view` |
| `PermissionGroup` | Домен / модуль для UI и doctor | `Documents`, `Dashboard` |
| `DynamicPermissionPattern` | Шаблон с плейсхолдерами | `app.team.{teamId}.documents.admin` + rule class |
| `Wildcard` | Только `*` на уровне роли | SuperAdmin |

Dynamic — **не** «любая строка», а:

```php
// объявление в коде панели
DynamicPermissionPattern::make('team.documents.admin')
    ->parameters(['teamId' => 'int'])
    ->resolver(TeamDocumentsAdminResolver::class);
```

Resolver проверяет, что `(teamId, user)` легитимны (команда существует, пользователь в команде). В БД хранится канонический ключ `app.team.5.documents.admin`, собранный по шаблону.

### 4.3. Типы grants (L2)

| Источник | Хранение | Когда использовать |
|----------|----------|-------------------|
| **Class role** | `roles.class_name` + PHP | Стандартные роли (Member, Admin) — как сейчас |
| **Role permission pivot** | `role_has_permissions` (только FK на catalog key) | Кастомная роль в Filament: подмножество static permissions без нового PHP-класса |
| **Direct user grant** | `model_has_permissions` | Временное право, импersonation, support |
| **Scope grant** | `model_has_scopes` | Ограничение выборки (уже есть зачаток) |

Важно: pivot **не** хранит произвольный `name varchar`, а `permission_key` (string), прошедший `Catalog::assertRegistered($key)`.

---

## 5. Предлагаемая структура классов (пакет `AzGuard\Registry`)

Новый namespace, не ломающий текущие `Roles`, `Guard`, `Support`.

### 5.1. Контракты

```php
interface PermissionDefinition
{
    public function key(): string;           // resolved: app.documents.view
    public function shortKey(): string;       // documents.view
    public function panelId(): string;
    public function group(): ?string;
    public function meta(): PermissionMeta;  // label, description — для UI
}

interface PermissionCatalog
{
    /** @return list<PermissionDefinition> */
    public function all(string $panelId): array;

    public function has(string $panelId, string $resolvedKey): bool;

    public function get(string $panelId, string $resolvedKey): ?PermissionDefinition;

  public function assert(string $panelId, string $resolvedKey): void; // InvalidPermissionKeyException
}

interface GrantSource
{
    public function permissionsFor(Authenticatable $user, string $panelId): PermissionSet;
}

interface DynamicPermissionResolver
{
    public function matches(string $resolvedKey): bool;
    public function validate(Authenticatable $user, string $resolvedKey): bool;
}
```

### 5.2. Реализации каталога (L1)

| Класс | Роль |
|-------|------|
| `EnumPermissionCatalogBuilder` | Сканирует `*Permission.php` enums панели (как GuardDoctor) |
| `PolicyAbilityCatalogBuilder` | Собирает abilities из `#[GateAbility]` (пересечение с enum — истина для «что реально проверяется») |
| `ConfigPermissionCatalogBuilder` | Доп. static keys из `config/az-guard.php` → `panels.app.extra_permissions` |
| `CompositePermissionCatalog` | Merge + dedupe; при конфликте — error в doctor |
| `CachedPermissionCatalog` | Кэш на deploy (аналог Spatie, но кэш **каталога**, не назначений) |

**Рекомендация:** канонический каталог = **пересечение** enum cases и зарегистрированных Gate abilities; enum без policy — error (уже есть); policy на string без enum — warning или opt-in flag.

### 5.3. Grant pipeline (L2)

```php
final class EffectivePermissionResolver
{
    public function __construct(
        private PermissionCatalog $catalog,
        /** @var iterable<GrantSource> */
        private iterable $sources,
    ) {}

    public function forUser(Authenticatable $user, string $panelId): PermissionSet
    {
        $set = PermissionSet::empty();

        foreach ($this->sources as $source) {
            $set = $set->merge($source->permissionsFor($user, $panelId));
        }

        return $set->filter(fn (string $key) => $this->catalog->has($panelId, $key) || $key === '*');
    }
}
```

| Класс | Роль |
|-------|------|
| `ClassRoleGrantSource` | Текущая логика `HasAzGuard::getAzPermissions()` |
| `DatabaseRoleGrantSource` | Права из `role_has_permissions` для ролей пользователя |
| `DirectGrantSource` | `model_has_permissions` |
| `DynamicGrantSource` | Разбор ключей по `DynamicPermissionPattern` + resolvers |

`HasAzGuard::hasAzPermission()` делегирует в `EffectivePermissionResolver` (с кэшем на request, как сейчас `azPermissionsCache`).

### 5.4. Роли: два режима (совместимость)

| Режим | `roles.class_name` | Права |
|-------|------------------|-------|
| **Code role** | FQCN `MemberRole` | Только из PHP `permissions()` |
| **Custom role** | `null` | Только из `role_has_permissions` (ключи из каталога) |

`RoleInterface` остаётся для code roles. Для custom — Eloquent `Role` + pivot, без `getRoleLogic()`.

Миграция: существующие роли с `class_name` — без изменений.

### 5.5. Интеграция с Gate / Doctor

| Компонент | Изменение |
|-----------|-----------|
| `GuardDoctor` | + проверка pivot keys; + dynamic patterns; + custom roles без orphan keys |
| `Authorizer` | без изменений по wildcard |
| `CheckPermission` attribute | опционально принимать enum вместо string |
| Artisan | `azguard:catalog` — dump JSON для UI/TS; `azguard:sync-roles` — seed code roles в БД |

### 5.6. Filament / админка (пакет `azguard/filament`)

- Multi-select прав **только** из `PermissionCatalog::all($panelId)`.
- Создание роли: выбор «шаблон (code class)» или «кастомная (pivot)».
- Запрет поля «новое permission вручную».

---

## 6. Схема БД (расширение, черновик)

Текущие таблицы сохраняются. Добавляются:

```sql
-- Канонические ключи (опционально: материализованный snapshot каталога для SQL-отчётов)
permission_catalog_snapshots (
  id, panel_id, key, group, source_enum, created_at
);

-- Только для custom roles / дополнений к code role
role_has_permissions (
  role_id, permission_key,  -- string, FK логический через app
  primary key (role_id, permission_key)
);

model_has_permissions (
  permission_key,
  model_type, model_id,
  expires_at nullable,
  primary key (...)
);
```

**Не добавлять** таблицу `permissions` с auto-increment и свободным `name`, чтобы не скатиться в Spatie.

Синхронизация snapshot (если нужна): команда `azguard:catalog:export` в CI, не runtime mutation.

---

## 7. Сценарии (acceptance)

### 7.1. Только code-first (как сейчас)

- MemberRole в PHP, user ↔ role в БД.
- Doctor зелёный; pivot пустой.

### 7.2. Кастомная роль в админке

- Админ создаёт роль `sales-manager` без PHP-класса.
- Выбирает `app.documents.view`, `app.documents.create` из списка каталога.
- Пользователю назначается роль → `DatabaseRoleGrantSource` отдаёт ключи.
- Попытка сохранить `app.hacked.delete` → validation error.

### 7.3. Временный grant

- Support выдаёт пользователю `app.documents.delete` на 24 ч через `model_has_permissions.expires_at`.
- Doctor в CI не ругается (ключ в каталоге); audit log — в приложении.

### 7.4. Dynamic team admin

- В каталоге объявлен pattern `app.team.{id}.admin`.
- В БД: `model_has_scopes` или grant с resolved key `app.team.12.admin`.
- `TeamAdminResolver` проверяет членство в team 12.

### 7.5. Плагин модуля

- Модуль регистрирует `PermissionCatalogProvider` в `PanelProvider::boot()`.
- Добавляет enum + policies в свой namespace; doctor сканирует через расширенный discovery path.

---

## 8. Валидация и наблюдаемость

| Механизм | Назначение |
|----------|------------|
| `azguard:doctor` | Статика: enum ↔ policy ↔ roles ↔ DB pivots |
| CI step | `doctor --fail-on-error` на PR |
| `azguard:catalog --json` | Генерация для TS `#[TypeScript]` / Filament labels |
| Events | `RolePermissionsSynced`, `DirectGrantCreated` → сброс request/cache |
| Metrics (опц.) | Счётчик отказов по unknown key (логировать в dev) |

---

## 9. Фазы реализации

### Фаза 0 — Документация и контракты (текущий этап)

- [x] Зафиксировать модель в `ideas/hybrid-permission-registry.md`
- [ ] ADR в `docs/guide/` со ссылкой на этот файл

### Фаза 1 — Catalog layer без БД

- [ ] `PermissionCatalog` + builders (enum + GateAbility)
- [ ] Рефактор `GuardDoctor` на catalog API
- [ ] `azguard:catalog` command
- [ ] Тесты: orphan enum, orphan role string, duplicate ability

### Фаза 2 — Composite grants (только code roles)

- [ ] `EffectivePermissionResolver` + `ClassRoleGrantSource`
- [ ] `HasAzGuard` перевести на resolver (поведение 1:1)
- [ ] Кэширование PermissionSet per request

### Фаза 3 — DB pivots для custom roles

- [ ] Миграции `role_has_permissions`, `model_has_permissions`
- [ ] `DatabaseRoleGrantSource`, `DirectGrantSource`
- [ ] Form Requests / Filament с whitelist из catalog
- [ ] Doctor: проверка всех ключей в БД ⊆ catalog

### Фаза 4 — Dynamic patterns

- [ ] `DynamicPermissionPattern`, resolvers
- [ ] Документация и 1 reference implementation (team scope)
- [ ] Расширенный doctor для pattern keys

### Фаза 5 — Filament UX

- [ ] Role resource: code vs custom
- [ ] Permission picker из catalog
- [ ] Audit / expires для direct grants

---

## 10. Риски и антипаттерны

| Риск | Митигация |
|------|-----------|
| Снова «мини-Spatie» с таблицей permissions | Нет свободного CRUD; только pivot keys + catalog |
| Два источника правды (enum vs policy) | Канон = пересечение; doctor обязателен |
| Производительность (N источников) | Request cache; опционально Redis для catalog snapshot |
| Сложность dynamic keys | Минимум шаблонов; явные resolver classes |
| Миграция существующих проектов | Фаза 1–2 без breaking; pivots opt-in |

**Антипаттерны:**

- Хранить «полный список permissions» только в БД.
- Проверять доступ через `in_array` в контроллере без Policy.
- Генерировать TS enum вручную без `azguard:catalog`.
- Использовать `Gate::define` для каждого permission без Policy (потеря доменных правил).

---

## 11. Связь с текущими файлами пакета

| Сейчас | После гибрида |
|--------|----------------|
| `HasAzGuard::getAzPermissions()` | Делегат `EffectivePermissionResolver` |
| `Role::getRoleLogic()` | `ClassRoleGrantSource` |
| `GuardDoctor` | Использует `PermissionCatalog` + проверка DB |
| `DiscoveryService` (roles) | Без изменений; + `PermissionCatalogProvider` discovery |
| `InteractsWithAzScopes` | Остаётся L3 domain filter; не путать с permission grants |
| `model_has_scopes` | Scoped **data access**; dynamic **admin** — через pattern grants |

---

## 12. Открытые вопросы (решить перед Фазой 3)

1. **Custom role + code class:** разрешать гибрид (PHP defaults + pivot extras) или XOR?
2. **Наследование ролей:** нужен ли `role_inherits` (Member ⊂ Admin) или композиция в PHP?
3. **Teams / multi-tenant:** panel-scoped catalog или global prefix + tenant id в dynamic pattern?
4. **Кэш assignments:** инвалидировать по событию модели или TTL?
5. **Совместимость с Sanctum API:** отдельный guard catalog `api.*` или та же панель `app`?

---

## 13. Краткий итог

AzGuard уже реализует **ядро code-first** (enum, policies, class roles, GuardDoctor). Следующий шаг — формализовать **Permission Catalog (L1)** и **composite Grant Sources (L2)**, чтобы контролируемо подключать БД для назначений и кастомных ролей **без** свободных dynamic permissions в стиле Spatie. Laravel Gate и Policy остаются местом доменной логики; база хранит только ссылки на известные ключи и scope-метаданные.
