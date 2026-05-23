# Контекстные роли и мультисайтовость (entity-scoped assignments)

План на будущую реализацию в AzGuard. Дополняет [hybrid-permission-registry.md](./hybrid-permission-registry.md): те же принципы code-first каталога, но назначение ролей и проверка прав **привязаны к сущности контекста** (workspace, команда, сайт, организация).

---

## 1. Контекст и цель

### 1.1. Задача

Пользователь может иметь **разные роли в разных контекстах**:

| Глобально (платформа) | В workspace «Acme» | В workspace «Beta» |
|-----------------------|--------------------|----------------------|
| `member` (базовый доступ) | `workspace-admin` | `workspace-viewer` |

Внутри контекста действуют **только** permissions этой роли (плюс опционально глобальные «базовые», если так задано политикой панели). Документы, настройки, API — проверяются с учётом **текущего контекста**, а не «суммы всех ролей со всех воркспейсов».

### 1.2. Принципы (в духе AzGuard)

| Принцип | Смысл |
|---------|--------|
| **Роли и permissions — в коде** | `MemberRole`, `WorkspaceAdminRole`, enum — как сейчас; контекст не создаёт новых permission-строк в БД. |
| **Контекст — в БД** | Храним *кто*, *какая роль*, *на какой сущности*; не храним произвольные ability. |
| **Типы контекста — в коде** | Реестр `ScopeEntity` (Team, Workspace, …): morph-типы, валидаторы, middleware. |
| **Разделение concern’ов** | **Права** (RBAC) ≠ **фильтр данных** (query scope); связаны, но не один механизм. |
| **Явный текущий контекст** | HTTP/API задаёт «в каком воркспейсе мы сейчас»; без контекста — только global-назначения (или запрет, по настройке панели). |

### 1.3. Чего избегаем

- Смешивать `model_has_scopes` (SQL filter) и «роль на воркспейс» в одной таблице без семантики.
- `hasAzPermission('view')` без контекста, когда запрос идёт в `/workspaces/{id}/...` — silent merge всех воркспейсов пользователя.
- Свободный morph на любую модель без регистрации в панели (orphan context types).
- Дублировать stancl/tenancy (отдельная БД на tenant) внутри AzGuard — это уровень инфраструктуры, не RBAC.

### 1.4. Связь с hybrid-permission-registry

```text
L1 Permission Catalog          ← без изменений (enum, doctor)
L2 Grant Sources               ← + ContextualRoleGrantSource
L2.5 Authorization Context     ← НОВЫЙ слой (текущий workspace/team)
L3 Gate + Policy               ← hasAzPermission(..., context?)
```

Каталог permissions **не** дублируется на каждый workspace: один `app.documents.view`; контекст определяет, **выдано ли** оно пользователю сейчас.

---

## 2. Текущее состояние AzGuard (baseline)

```
model_has_roles          user ↔ role          (глобально, без контекста)
model_has_scopes         user ↔ scope_class ↔ scope_entity   (фильтр Eloquent)
```

**`HasAzGuard::getAzPermissions()`** — объединяет permissions всех ролей пользователя **без разреза по сущности**.

**`InteractsWithAzScopes`** — global scope на модели по `scope_entity_type` + `scope_class`; не знает про роли.

**Пробел:** нет `model_has_roles` с `scope_entity_*`, нет `CurrentAuthorizationContext`, Policy не получает «в каком воркспейсе проверяем».

---

## 3. Терминология

| Термин | Определение |
|--------|-------------|
| **Scope entity** (контекстная сущность) | Eloquent-модель-якорь: `Workspace`, `Team`, `Site`. |
| **Authorization context** | Пара `(scope_entity_type, scope_entity_id)` + опционально метаданные (slug, panel). |
| **Global assignment** | Роль без контекста (`context_id = null`) — платформа, Filament admin, fallback. |
| **Scoped assignment** | Роль привязана к конкретному workspace/team. |
| **Effective context** | Контекст, выбранный для текущего запроса (middleware, route, job payload). |
| **Data scope** | Ограничение выборки (`ScopeInterface::apply`) — кто какие **строки** видит. |
| **Permission scope** | Какие **abilities** активны в данном контексте. |

Один пользователь:

```text
Global:     crm-admin
Workspace 5: workspace-member  → documents.view, documents.create
Workspace 9: workspace-guest   → documents.view
```

В запросе с `context = workspace:5` → permissions member, **не** guest и **не** автоматически union с workspace 9.

---

## 4. Сравнительный анализ

### 4.1. Spatie `laravel-permission` + Teams

| Аспект | Поведение |
|--------|-----------|
| Модель | `team_id` (или кастомный foreign key) на pivot ролей/permissions. |
| Плюсы | Привычная схема, `setPermissionsTeamId()`, интеграции. |
| Минусы | Permission по-прежнему в БД; teams — один тип сущности; слабая связь с Policy. |
| Урок | Pivot **(user, role, team_id)** — хороший низкоуровневый паттерн; взять идею nullable `context`, но типизировать через реестр ScopeEntity. |

### 4.2. Bouncer `scope` column

| Аспект | Поведение |
|--------|-----------|
| Модель | Integer `scope` на abilities/roles; опционально `restricted_to` morph. |
| Плюсы | Гибкость ability на модель (`edit` только на этот Post). |
| Минусы | Не RBAC-роли в привычном смысле; scope как число не self-documenting. |
| Урок | Различать **role-on-entity** (workspace admin) и **ability-on-entity** (edit this post) — второе остаётся в Policy. |

### 4.3. stancl/tenancy / отдельная БД на tenant

| Аспект | Поведение |
|--------|-----------|
| Модель | Изоляция данных на уровне connection/schema. |
| Урок | AzGuard **не заменяет** tenancy: при single-DB multi-site контекст = строка `workspace_id`; при multi-DB tenant resolver живёт в приложении, AzGuard получает уже resolved `ScopeEntity`. |

### 4.4. Laravel Policy + `$user->can()` без пакета

| Аспект | Поведение |
|--------|-----------|
| Паттерн | `$user->can('update', [$document, $workspace])` — второй аргумент вручную. |
| Урок | AzGuard стандартизирует контекст через `AuthorizationContext` + middleware, чтобы не передавать workspace в каждый вызов. |

### 4.5. Сводка позиционирования

| | Spatie Teams | Bouncer scope | AzGuard (цель) |
|---|--------------|---------------|----------------|
| Тип контекста | Один team FK | Числовой scope | **Реестр ScopeEntity (morph)** |
| Permissions | БД | БД | **Code catalog** |
| Роль на сущность | Pivot + team_id | Ability + entity | **Scoped role assignment** |
| Фильтр запросов | Вручную | `where` в ability | **`ScopeInterface` + context** |
| Статическая проверка | Слабая | Слабая | **Doctor + CI** |

---

## 5. Целевая модель

### 5.1. Четыре слоя (дополнение к hybrid registry)

```text
┌──────────────────────────────────────────────────────────────┐
│ Scope Entity Registry (code)                                 │
│   WorkspaceScopeEntity, TeamScopeEntity — morph, policies    │
└────────────────────────────┬─────────────────────────────────┘
                             │
┌────────────────────────────▼─────────────────────────────────┐
│ Role Assignments (DB)                                        │
│   global: (user, role, context=null)                         │
│   scoped: (user, role, workspace_id=5)                       │
└────────────────────────────┬─────────────────────────────────┘
                             │
┌────────────────────────────▼─────────────────────────────────┐
│ Authorization Context (request / job)                        │
│   CurrentContext::get() → Workspace#5                        │
│   EffectivePermissionResolver(panel, user, context)          │
└────────────────────────────┬─────────────────────────────────┘
                             │
┌────────────────────────────▼─────────────────────────────────┐
│ Gate + Policy + Data Scopes                                  │
│   hasAzPermission($key, $context?)                           │
│   Document::query() + WorkspaceDataScope                     │
└──────────────────────────────────────────────────────────────┘
```

### 5.2. Правила слияния permissions

Настраивается на уровне **Panel** (`Panel::contextMergeStrategy()`):

| Стратегия | Поведение | Когда |
|-----------|-----------|--------|
| `contextOnly` | Только scoped-роли текущего контекста | Строгий multi-site CRM |
| `globalPlusContext` | Union global + scoped | Глобальный member + workspace admin |
| `contextOverridesGlobal` | Scoped заменяет global для совпадающих ключей | Редко |
| `denyWithoutContext` | Если маршрут требует context — 403 без него | API `/workspaces/{id}/*` |

По умолчанию для `app` панели: **`globalPlusContext`** с документированным риском; для API workspace — **`contextOnly`** на маршрутах с `{workspace}`.

Wildcard `*` в scoped-роли действует **только внутри контекста**, не на всю платформу (если не global assignment).

### 5.3. Data scope vs permission scope

| | Permission scope | Data scope |
|---|------------------|------------|
| Вопрос | Может ли пользователь **вызвать** action? | Какие **строки** попадут в index? |
| Механизм | `hasAzPermission` + Policy | `ScopeInterface::apply(Builder)` |
| Привязка | Role assignment + context | `model_has_scopes` или auto из context |
| Пример | `documents.delete` в workspace 5 | `where workspace_id = 5` |

**Рекомендация:** при наличии effective context автоматически применять зарегистрированный `DataScope` для моделей, помеченных `#[ScopedTo(Workspace::class)]`, если нет явного `model_has_scopes` — опционально, флаг панели.

### 5.4. Иерархия контекстов (опционально, фаза 2+)

```text
Organization
  └── Workspace
        └── Team
```

- **Наследование:** роль на Organization не автоматически на Workspace — явные правила в `ContextHierarchy` (код).
- **Effective context** — один «лист» за запрос (обычно Workspace); вложенность — через resolver `Team within Workspace`.

Не реализовывать глубокое дерево в v1: один уровень `ScopeEntity` на панель достаточно.

---

## 6. Схема БД (черновик)

### 6.1. Расширение `model_has_roles`

Текущую таблицу расширить (миграция additive):

```sql
model_has_roles (
  role_id,
  model_type, model_id,          -- пользователь
  scope_entity_type NULLABLE,    -- App\Models\Workspace
  scope_entity_id   NULLABLE,
  -- уникальность: один и тот же role+user+context не дублируется
  UNIQUE (role_id, model_id, model_type, scope_entity_type, scope_entity_id)
)
```

`scope_entity_* = NULL` → global assignment.

Опционально:

```sql
model_has_role_metadata (
  model_has_role_id,  -- или composite key
  key, value,         -- invited_at, invited_by, expires_at
)
```

### 6.2. `model_has_scopes` — без слияния с ролями

Оставить для **data filtering** и особых случаев (доступ к чужому workspace read-only через отдельный scope class). Не дублировать роль в `scope_class`, если уже есть scoped role assignment.

### 6.3. Кэш / индексы

- Индекс `(model_id, model_type, scope_entity_type, scope_entity_id)` для загрузки всех ролей пользователя в контексте.
- Request-cache: `Map<contextKey, PermissionSet>` на пользователя.

---

## 7. Структура классов (пакет `AzGuard\Context`)

### 7.1. Контракты

```php
interface ScopeEntityDefinition
{
    /** @return class-string<Model> */
    public function modelClass(): string;

    public function type(): string;           // 'workspace', 'team'
    public function panelId(): string;

    /** Можно ли назначать роли на этот тип в UI */
    public function allowsRoleAssignment(): bool;

    /** Resolver: пользователь вообще может «войти» в этот контекст */
    public function membershipResolver(): ScopeMembershipResolver;
}

interface AuthorizationContext
{
    public function type(): string;
    public function id(): int|string;
    public function entity(): ?Model;
    public function cacheKey(): string;      // 'workspace:5'
    public function isGlobal(): bool;
}

interface ScopeMembershipResolver
{
    public function userBelongsTo(Authenticatable $user, Model $entity): bool;
}

interface ContextualGrantSource extends GrantSource
{
    public function permissionsFor(
        Authenticatable $user,
        string $panelId,
        ?AuthorizationContext $context,
    ): PermissionSet;
}
```

### 7.2. Реестр и текущий контекст

| Класс | Роль |
|-------|------|
| `ScopeEntityRegistry` | Все `ScopeEntityDefinition` панели (из `PanelProvider`) |
| `AuthorizationContextManager` | `set` / `get` / `forget` (request singleton) |
| `AuthorizationContextFactory` | Из route param `workspace`, header `X-Workspace-Id`, subdomain |
| `NullAuthorizationContext` | Global-only запросы |

### 7.3. Grant resolution

| Класс | Роль |
|-------|------|
| `GlobalRoleGrantSource` | `scope_entity_id IS NULL` |
| `ContextualRoleGrantSource` | pivot с совпадающим context |
| `EffectivePermissionResolver` | merge по стратегии панели (см. §5.2) |

`HasAzGuard` расширить:

```php
public function hasAzPermission(
    string $permission,
    ?AuthorizationContext $context = null,
): bool;

public function getAzPermissions(?AuthorizationContext $context = null): Collection;

/** Все контексты, куда пользователь допущен (для switcher UI) */
public function authorizationContexts(string $panelId): Collection;
```

Если `$context === null` → берётся `AuthorizationContextManager::get()`; если и там null → только global (или exception по конфигу).

### 7.4. HTTP / middleware

| Middleware | Действие |
|------------|----------|
| `azguard.context:workspace` | Resolve entity, validate membership, `ContextManager::set()` |
| `azguard.context.optional` | То же, но не 403 если нет param |
| `azguard.roles` | Eager load roles **с учётом** current context (см. ниже) |

Порядок: `auth` → `azguard.panel` → **`azguard.context`** → `azguard.roles` → `check.access`.

`LoadAzGuardRoles` — загружать:

```php
$user->load(['roles' => fn ($q) => $q
    ->where(fn ($q) => $q->whereNull(scope columns)->orWhere(matches current context))
]);
```

### 7.5. Policy и Gate

```php
#[GateAbility(permission: DocumentsPermission::View, requiresContext: Workspace::class)]
public function canView(User $user, Document $document): bool
{
    return $user->hasAzPermission(
        AppGuard::permission(DocumentsPermission::View),
    ) && $document->workspace_id === AuthorizationContextManager::get()?->id();
}
```

Doctor:

- ability с `requiresContext` → предупреждение, если middleware не зарегистрирован на маршрутах панели;
- scoped role class не содержит permissions вне catalog.

**Альтернатива:** trait `AuthorizesInContext` с ` $this->authorizeInContext(...)`.

### 7.6. Inertia / API

Shared props (приложение, не пакет core):

```php
'auth' => [
    'context' => CurrentContext::toArray(),      // { type, id, name }
    'contexts' => $user->authorizationContexts(), // switcher
    'permissions' => $abilityProjector->forContext(), // опционально, deferred
],
```

Switcher меняет context (cookie / session / POST) → следующий request с новым middleware resolve.

### 7.7. Связь с `ScopeInterface` (data)

```php
final class WorkspaceDataScope implements ScopeInterface
{
    public function apply(Builder $builder, Model $user, ?Model $entity): void
    {
        $ctx = AuthorizationContextManager::get();
        if ($ctx?->type() === 'workspace') {
            $builder->where('workspace_id', $ctx->id());
        }
    }
}
```

Регистрация: `#[ScopedTo(Workspace::class)]` на модели `Document` или конфиг панели `data_scopes`.

---

## 8. Объявление в guard-модуле приложения

```text
app/Guards/App/
  Context/
    WorkspaceScopeEntity.php      # ScopeEntityDefinition
    WorkspaceMembershipResolver.php
  Roles/
    WorkspaceMemberRole.php       # permissions только workspace-уровня
    WorkspaceAdminRole.php
  Middleware/                     # опционально тонкие обёртки
```

`PanelProvider`:

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->id('app')
        ->contextMergeStrategy(ContextMergeStrategy::GlobalPlusContext)
        ->scopeEntities([
            WorkspaceScopeEntity::class,
        ]);
}
```

Seeder:

```php
$user->assignAzRole(
    role: $workspaceMemberRole,
    context: AuthorizationContext::for($workspace),
);
```

---

## 9. Сценарии (acceptance)

### 9.1. Пользователь в двух workspace с разными ролями

- WS-A: `workspace-admin`, WS-B: `workspace-viewer`.
- Запрос с context A → `documents.delete` разрешён (если в admin role).
- Запрос с context B → delete запрещён.
- Запрос без context → поведение по стратегии (`denyWithoutContext` или только global perms).

### 9.2. Глобальный admin + workspace member

- Global: `crm-admin` с `*`.
- В WS-A: `workspace-member` (узкий набор).
- Стратегия `globalPlusContext`: в WS-A всё ещё `*` с global (если не ограничить явно).
- Стратегия `contextOnly` на workspace routes: в WS-A только member perms.

→ Документировать: для «настоящего» ограничения superuser внутри workspace не давать global `*` или ввести `contextSuppressesGlobalWildcard`.

### 9.3. Приглашение в команду

- Создаётся scoped assignment `(user, team-member, team:12)`.
- `TeamMembershipResolver` проверяет активное членство.
- Удаление из team → cascade delete assignments (observer приложения).

### 9.4. Filament / админка платформы

- Global roles только в admin panel.
- Workspace members управляются в app UI или отдельном Filament tenant panel с обязательным `azguard.context`.

### 9.5. Queue / jobs

```php
AuthorizationContextManager::run(
    context: AuthorizationContext::for($workspace),
    callback: fn () => $action->handle(),
);
```

Без этого — только global permissions в job.

---

## 10. Валидация (Doctor + CI)

| Проверка | Уровень |
|----------|---------|
| Scoped assignment на незарегистрированный morph type | error |
| Role `permissions()` содержат ключи не из catalog | error (уже есть) |
| Route group `{workspace}` без `azguard.context` | warning |
| `requiresContext` ability без context в тестах | warning |
| Дубликат (user, role, same context) | error |
| User assigned to context без membership | error (опционально strict mode) |

Команды:

- `azguard:contexts {user}` — список контекстов и ролей.
- `azguard:doctor --context` — расширенный отчёт.

---

## 11. Фазы реализации

### Фаза 0 — Документация

- [x] Этот файл
- [ ] Ссылка из `docs/guide/` + перекрёстные ссылки с hybrid registry

### Фаза 1 — Context primitives (без смены pivot)

- [ ] `AuthorizationContext`, `AuthorizationContextManager`
- [ ] `ScopeEntityRegistry` в PanelProvider
- [ ] Middleware `azguard.context`
- [ ] Тесты: set/get/forget, job wrapper

### Фаза 2 — Scoped pivot

- [ ] Миграция nullable `scope_entity_*` на `model_has_roles`
- [ ] `assignAzRole($role, ?AuthorizationContext $context)`
- [ ] `ContextualRoleGrantSource` + merge strategies
- [ ] Обновить `HasAzGuard::hasAzPermission($perm, $ctx?)`
- [ ] `LoadAzGuardRoles` с фильтром по context

### Фаза 3 — Policies и data scopes

- [ ] `requiresContext` на `GateAbility` (опционально)
- [ ] Doctor: routes + context
- [ ] `WorkspaceDataScope` reference + `ScopedTo` attribute
- [ ] Inertia props contract (документация для приложения)

### Фаза 4 — UX и Filament

- [ ] Context switcher API
- [ ] UI назначения ролей на workspace в админке
- [ ] Membership resolver hooks (invite flow)

### Фаза 5 — Иерархия и advanced

- [ ] Parent context inheritance (если нужно)
- [ ] `contextSuppressesGlobalWildcard`
- [ ] Audit log assignments

**Зависимость:** Фаза 2 hybrid registry (catalog) желательна раньше Filament picker, но scoped pivot можно внедрить на существующих enum.

---

## 12. Риски и антипаттерны

| Риск | Митигация |
|------|-----------|
| Union всех workspace permissions | Обязательный `CurrentContext` на scoped routes |
| N+1 при загрузке ролей | Eager load с фильтром; cache per request |
| Global `*` обходит workspace isolation | Стратегия + отдельные global roles без `*` |
| Путаница scope vs role | Разные таблицы/термины в доке и API |
| Забыли context в job | `AuthorizationContextManager::run` + static analysis opt |

**Антипаттерны:**

- Проверять `$user->roles` в Policy без context.
- Хранить `workspace_id` только в session без сверки membership.
- Создавать отдельный permission enum на каждый workspace.
- Использовать `model_has_scopes` вместо scoped role «потому что уже есть таблица».

---

## 13. Открытые вопросы

1. **Один или несколько активных контекстов** на запрос? (Рекомендация: один primary; multi — только для aggregate dashboard с явным API.)
2. **Session vs URL** для current workspace: `/w/{slug}` vs `X-Workspace-Id`?
3. **Guest / public** workspace links — нужен ли `LimitedContext` без auth?
4. **Impersonation:** контекст impersonator или impersonated?
5. **Совместимость с hybrid registry:** custom role pivot — scoped тем же `scope_entity_*`?
6. **Название в API:** `context`, `tenant`, `workspace` — единый alias в config.

---

## 14. Краткий итог

Мультисайтовость в AzGuard — это **scoped role assignments** на зарегистрированные **ScopeEntity**, плюс явный **Authorization Context** на запрос. Каталог permissions остаётся code-first; в БД — только «кто какую роль имеет где». Фильтрация данных — отдельный слой (`ScopeInterface`), синхронизированный с контекстом, но не смешанный с RBAC. Реализация поэтапно: context manager → расширение pivot → Policy/middleware → UI switcher.

См. также: [hybrid-permission-registry.md](./hybrid-permission-registry.md).
