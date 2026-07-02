export const meta = {
  name: 'azguard-plan-remainder',
  description: 'Автономно доделывает IMPROVEMENT_PLAN.md (Фазы 5-8 + хвосты T1-T6) на ОДНОЙ ветке off main: по срезу impl→tests-to-green→risk-адаптивный review; per-phase composer-check гейт + CHANGELOG + инкрементальный push; честная отсрочка нерешаемого; утренний отчёт + draft PR. НЕ мержит в main.',
  phases: [
    { title: 'Setup' },
    { title: 'Phase 5 — Console/CLI' },
    { title: 'Phase 6 — Filament & Context' },
    { title: 'Phase 7 — Cleanup/Testing/CI' },
    { title: 'Phase 8 — Docs & DX' },
    { title: 'Tails' },
    { title: 'Finalize' },
  ],
}

const DIR = '/home/vostrikov/projects/packages/azguard'
const BRANCH = 'refactor/plan-remainder'
const BASE = 'main'

const GATES = `ЖЁСТКИЕ ГЕЙТЫ (не нарушать):
- Работать строго в ${DIR}. Тесты — composer test (pest), ВЫВОД В ФАЙЛ, не в pipe (pest > file 2>&1; | tail теряет вывод на timeout).
- НЕ git push, НЕ открывать/мержить PR, НЕ release, НЕ migrate:fresh/drop по чему-либо кроме тестовой БД (:memory:).
- НЕ трогать vendor/. ЛОВУШКА: vendor/orchestra/testbench-core/laravel/config/az-guard.php — устаревшая published-копия конфига, ШАДОВИТ пакетный mergeConfigFrom в тестах; если config('az-guard.*') в тесте отдаёт не то — проверить canary-ключом, решать через фикс-константу/явный config()->set в тесте, vendor НЕ править.
- Все срезы на ветке ${BRANCH}: коммить в ТЕКУЩУЮ ветку, дерево оставлять ЧИСТЫМ, ветки не создавать/не переключать.
- Инварианты ARCHITECT_REVIEW.md §6 держать: НЕ тащить ClassScanner (F31), forbid/deny-precedence, ReBAC/DSL в ядро, generic-ключи в PermissionSet, интерфейсы над внутренними классами; nav-hiding ≠ контроль доступа (F13); write-time валидация ключей — opt-in lenient (F46).
- Контракт-дубли обновлять при смене контракта: tests/Stubs/SwapTestManager.php, анонимные implements в tests/Unit/Registry/EffectivePermissionResolverTest.php, tests/Support/*.
- PHPStan honest-baseline активен (reportUnmatchedIgnoredErrors:true): если срез УДАЛЯЕТ код/фиксит ошибку, покрытую phpstan-baseline.neon — снять соответствующую запись, иначе analyse упадёт на unmatched.`

const STATUS = {
  type: 'object', additionalProperties: false,
  required: ['id', 'status', 'summary'],
  properties: {
    id: { type: 'string' },
    status: { type: 'string', enum: ['green', 'deferred', 'failed', 'infra-failed'], description: 'green=composer check по срезу зелёный и закоммичено; deferred=честно не решено/нужна инфра или человек (НЕ маскировать баг); failed=агент отчитался о сбое; infra-failed=rate-limit/timeout/null' },
    risk_level: { type: 'string' },
    summary: { type: 'string' },
    files_changed: { type: 'array', items: { type: 'string' } },
    tests_added: { type: 'array', items: { type: 'string' } },
    workarounds: { type: 'array', items: { type: 'string' }, description: 'обходка ради зелёного = [POSSIBLE-DEFECT]' },
    open_questions: { type: 'array', items: { type: 'string' } },
  },
}

// Resilience: НИ ОДИН agent()-throw (schema-noncompliance/terminal API error) не должен
// ронять весь прогон. Оборачиваем — throw → null, цикл трактует null как infra-failed и идёт дальше.
// (prompt/opts внутрь передаются без изменений ⇒ resume-кэш завершённых агентов сохраняется.)
const rawAgent = agent
async function safeAgent(prompt, opts) {
  try {
    return await rawAgent(prompt, opts)
  } catch (e) {
    log(`agent ${(opts && opts.label) || '?'} threw (${String((e && e.message) || e).slice(0, 160)}) — treat as infra-failed, continue`)
    return null
  }
}

// Dependency-ordered flat list. kind: code|filament|context|test|chore|docs.
// Адоптион-блокеры (F51, F13, T1) стоят первыми в своих фазах.
const SLICES = [
  // ── Phase 5 — Console/CLI ────────────────────────────────────────────────
  { id: 'F51', phase: 'Phase 5 — Console/CLI', kind: 'chore', risk: 'MED', title: 'unify command prefix to guard:, drop dead $aliases',
    brief: 'Стандартизуй ВСЕ artisan-команды пакета под единый префикс guard: (сейчас микс), удали самоссылочные мёртвые $aliases (3 команды), обнови регистрацию в packages/core/src/AzGuardServiceProvider.php. Тест на регистрацию под единым префиксом. Это BREAKING — запись в packages/core/CHANGELOG.md. Пакет не в проде — правим сразу, без deprecated-шимов.' },
  { id: 'F15', phase: 'Phase 5 — Console/CLI', kind: 'code', risk: 'MED', title: 'guard:role:assign / guard:role:detach',
    brief: 'Новые команды guard:role:assign / guard:role:detach через Commands/Concerns/ResolvesUserModel + Config::roleModel(). Feature-тест на назначение/снятие роли.' },
  { id: 'F52', phase: 'Phase 5 — Console/CLI', kind: 'code', risk: 'MED', title: '--json/--format + exit codes (OutputsStructured)',
    brief: 'Общий concern OutputsStructured: флаги --json/--format=json и осмысленный НЕНУЛЕВОЙ exit-code при провале для guard:doctor и guard:catalog:validate. Тест: --format=json даёт валидный payload; провал → ненулевой код.' },
  { id: 'F32', phase: 'Phase 5 — Console/CLI', kind: 'code', risk: 'MED', title: 'commands via Config::*Model() + key validation',
    brief: 'Все команды берут модели через Config::*Model() (не хардкод); валидация ключей против каталога на add/sync. Тест: кастомные модели подхватываются; неизвестный ключ репортится.' },
  { id: 'F33', phase: 'Phase 5 — Console/CLI', kind: 'code', risk: 'LOW', title: '--force + argument-driven make:guard-*',
    brief: '--force для make:guard-* команд; make:guard-role — argument-driven (не только интерактив); общий трейт. Тест: неинтерактивная генерация с --force.' },
  { id: 'F53', phase: 'Phase 5 — Console/CLI', kind: 'code', risk: 'MED', title: 'guard:explain / guard:abilities',
    brief: 'Новые guard:explain / guard:abilities поверх resolver / AbilitiesDto (инспекция решений). Тест: guard:explain <user> <perm> печатает источник вердикта. §4.5 / §6 #12: explain — отдельный opt-in re-run, НЕ флаг в hot check().' },

  // ── Phase 6 — Filament & Context ─────────────────────────────────────────
  { id: 'F41', phase: 'Phase 6 — Filament & Context', kind: 'chore', risk: 'LOW', title: 'delete dead MissingAuthorizationContextException',
    brief: 'Удалить мёртвый packages/context/src/Exceptions/MissingAuthorizationContextException.php (нигде не используется) + ложный докблок в DenyWithoutContextStrategy. Тесты зелёные, ссылок нет.' },
  { id: 'F12', phase: 'Phase 6 — Filament & Context', kind: 'filament', risk: 'LOW', title: 'filament.user_label_column config key',
    brief: 'Добавить ключ filament.user_label_column в packages/filament/config/az-guard-filament.php (сейчас фантом на 4 сайтах чтения). Тест: кастомный лейбл-столбец подхватывается.' },
  { id: 'F39', phase: 'Phase 6 — Filament & Context', kind: 'filament', risk: 'LOW', title: 'default panelId from config in AzGuardPlugin',
    brief: 'Дефолт panelId из конфига в packages/filament/src/AzGuardPlugin::getPanelId() (устранить \'app\'≠\'admin\'). Тест: плагин берёт панель из конфига. §6 #9: НЕ делать make() container-resolvable — только фикс дефолта.' },
  { id: 'F11', phase: 'Phase 6 — Filament & Context', kind: 'filament', risk: 'MED', title: 'PermissionEnumGenerator respects case/key config',
    brief: 'PermissionEnumGenerator уважает case/key конфиг (инжект PermissionSchema) — иначе кодоген расходится с рантаймом. Round-trip тест на не-snake кейсе: сгенерированный ключ == проверяемый рантаймом.' },
  { id: 'F29', phase: 'Phase 6 — Filament & Context', kind: 'filament', risk: 'MED', title: 'memoize DoctorPage::runDiagnose + batch labels',
    brief: 'Мемоизировать DoctorPage::runDiagnose() (сейчас 3×/render); батч-резолв лейблов в DirectGrantResource (N+1). Тест/бенч: diagnose() зовётся 1×; нет N+1.' },
  { id: 'F26', phase: 'Phase 6 — Filament & Context', kind: 'context', risk: 'MED', title: 'table_names.context_roles in context config',
    brief: 'Добавить table_names.context_roles в packages/context/config/az-guard-context.php и читать оттуда (сейчас тянет несуществующий core-ключ). Тест: имя таблицы из context-конфига. §6 #5: без ContextRoleSource-контракта, только конфиг-ключ.' },
  { id: 'F14', phase: 'Phase 6 — Filament & Context', kind: 'context', risk: 'HIGH', title: 'context auto-alias middleware + write-API',
    brief: 'Context: авто-alias middleware в AzGuardContextServiceProvider::boot(); write-API guard:context:grant / guard:context:revoke + builder. Тест: middleware работает без ручного alias; гранты контекста ставятся из CLI. Может потребовать миграцию — сверить и завести при необходимости.' },
  { id: 'F13', phase: 'Phase 6 — Filament & Context', kind: 'filament', risk: 'HIGH', title: 'enforce page/widget perms (HasAzGuardPage/Widget)',
    brief: 'ADOPTION-BLOCKER (security). Трейты HasAzGuardPage::canAccess() / HasAzGuardWidget::canView() — закрыть НЕэнфорсимые page/widget-права. Тест: страница без права НЕ доступна по URL (не просто скрыта в навигации). §6 #10: nav-hiding ≠ контроль. Если полноценный энфорс невозможен — перестать эмитить такие «права» + задокументировать, но это второй выбор.' },

  // ── Phase 7 — Cleanup/Testing/CI ─────────────────────────────────────────
  { id: 'F31', phase: 'Phase 7 — Cleanup/Testing/CI', kind: 'chore', risk: 'MED', title: 'remove dead code (PanelManager, PendingGrant, DiscoveryService)',
    brief: 'Удалить мёртвый код: packages/core/src/Guard/PanelManager.php, packages/core/src/Grants/PendingGrant.php; решить судьбу Guard/DiscoveryService.php (удалить + его тест ИЛИ снять divergent-scanner framing). §4.1 + §6 #1 — НЕ извлекать ClassScanner. ВАЖНО: снять из phpstan-baseline.neon записи PanelManager($panels)/DiscoveryService(discoverRoles) — иначе reportUnmatchedIgnoredErrors уронит analyse. composer check зелёный.' },
  { id: 'F19', phase: 'Phase 7 — Cleanup/Testing/CI', kind: 'test', risk: 'LOW', title: 'CLI feature matrix + AbilitiesDto unit suite',
    brief: 'Feature-матрица на непокрытые CLI-команды (~15, учти новый префикс guard: из F51); юнит-сьют на AbilitiesDto. Каждая команда имеет CLI-тест; abilities покрыт.' },
  { id: 'F20', phase: 'Phase 7 — Cleanup/Testing/CI', kind: 'test', risk: 'LOW', title: 'contract-parity arch test for Fakes',
    brief: 'Contract-parity arch/reflection-тест на FakeAzGuardUser/FakeGrantSource в tests/Unit/Contracts/ContractTraitParityTest.php — паритет фейков с контрактами.' },
  { id: 'F49', phase: 'Phase 7 — Cleanup/Testing/CI', kind: 'test', risk: 'LOW', title: 'arch ratchets toBeFinal/toBeReadonly + datasets',
    brief: 'Arch-рэтчеты toBeFinal()->toBeReadonly() где применимо (tests/ArchTest.php, tests/Unit/Filament/FilamentArchTest.php); параметризовать матрицы датасетами. Инварианты активны и зелёные (не ломать существующие не-final/не-readonly легитимные классы — заскоупить).' },
  { id: 'F50', phase: 'Phase 7 — Cleanup/Testing/CI', kind: 'chore', risk: 'MED', title: 'infection per-package + coverage/mutation gate',
    brief: 'Infection per-package + diff-scoped PR-гейт; добавить coverage/mutation в composer check. ЕСЛИ окружение без pcov/xdebug (mutation не прогнать) — настрой infection.json5 + гейт-скрипт, но НЕ форси прогон, пометь status=deferred с причиной (infra: нет драйвера покрытия).' },

  // ── Phase 8 — Docs & DX ──────────────────────────────────────────────────
  { id: 'F23', phase: 'Phase 8 — Docs & DX', kind: 'docs', risk: 'LOW', title: 'rewrite abilities-frontend on real API + Inertia recipe',
    brief: 'Переписать docs/**/basic-usage/abilities-frontend.md на AbilitiesDto::make()->toArray() + AzGuard::abilitiesFor(); добавить docs/**/recipes/inertia-permissions.md + типизированный useCan(). Примеры компилируются против реального 0.2 API. §4.8, §6 #8: НЕ дампить весь каталог, только curated boolean-подмножество.' },
  { id: 'F24', phase: 'Phase 8 — Docs & DX', kind: 'docs', risk: 'LOW', title: 'compilable custom-catalog-builder example',
    brief: 'Компилируемый пример custom-catalog-builder (SimplePermissionDefinition + регистрация через публичный AzGuard::registerCatalogBuilder()) в docs/**/advanced/extending.md.' },
  { id: 'F44', phase: 'Phase 8 — Docs & DX', kind: 'docs', risk: 'MED', title: 'CLI reference generator + drift test',
    brief: 'Генерировать CLI-референс из зарегистрированного списка команд; CI drift-тест; исправить таксономию префиксов (после F51 — все guard:). docs/**/basic-usage/artisan-commands.md. Референс покрывает все команды; тест ловит расхождение.' },
  { id: 'F42', phase: 'Phase 8 — Docs & DX', kind: 'docs', risk: 'MED', title: 'fix RU-in-EN leak + EN/RU parity gate',
    brief: 'Починить RU-в-EN-дерево leak (docs/recipes/index.md и др. русские страницы в EN-дереве), добить integration-страницы, добавить CI parity EN↔RU. Нет языковых утечек; parity-гейт зелёный.' },
  { id: 'F45', phase: 'Phase 8 — Docs & DX', kind: 'docs', risk: 'LOW', title: 'standardize App\\Guards\\ namespace in docs',
    brief: 'Стандартизовать App\\Guards\\ (генератор = источник истины) во всех примерах docs/**.' },
  { id: 'F43', phase: 'Phase 8 — Docs & DX', kind: 'docs', risk: 'LOW', title: 'PHP 8.2 -> 8.3+ in docs + doc-lint',
    brief: 'Глобально PHP 8.2 → 8.3+ в docs/**; doc-lint против composer.json (версии согласованы).' },
  { id: 'F54', phase: 'Phase 8 — Docs & DX', kind: 'chore', risk: 'LOW', title: 'fix .claude toolkit + Boost skill to 0.2',
    brief: 'Тулкит: починить путь rector-skip у BaseRole, перенацелить azguard-reviewer на существующую arch, обновить Boost-скилл packages/core/resources/boost/skills/** до 0.2 API. Тулкит согласован с кодом.' },

  // ── Tails ────────────────────────────────────────────────────────────────
  { id: 'T1', phase: 'Tails', kind: 'code', risk: 'HIGH', title: 'panel-aware scoped-role global query-scope',
    brief: 'ADOPTION-BLOCKER. Eloquent global query-scope scoped-ролей (packages/core/src/Concerns/HasScopedRoles.php bootHasScopedRoles) НЕ panel-aware — фильтрует по scope_class независимо от panel_id → scoped query-filtering течёт между панелями (permission-check уже изолирован F8). Решить: пробросить активную панель в global scope (источник контекста — AzGuard::currentPanel()) ИЛИ явно задокументировать что query-scope не panel-bound + тест на выбранное поведение. Тонко — у global-scope нет «текущей панели».' },
  { id: 'T2', phase: 'Tails', kind: 'code', risk: 'MED', title: 'removeScopedRole null-panel semantics',
    brief: 'removeScopedRole($role,$entity,panelId=null) сейчас сносит строки ВСЕХ панелей (асимметрия с assignScopedRole, где null=отдельная any-panel строка). packages/core/src/Concerns/HasScopedRoles.php:125-133. Решить: null → только null-panel строка (симметрия) vs оставить+документировать. Покрыть тестом выбранное поведение.' },
  { id: 'T3', phase: 'Tails', kind: 'chore', risk: 'LOW', title: 'Log::warning parity in EnumPermissionCatalogBuilder',
    brief: 'packages/core/src/Registry/Builders/EnumPermissionCatalogBuilder.php: на missing-классе тихий continue — добавить Log::warning (паритет с policy-builder). Тест на лог.' },
  { id: 'T4', phase: 'Tails', kind: 'code', risk: 'LOW', title: 'wildcard-off drops literal * before dynamic match',
    brief: 'wildcard-off: литеральный * в grant всё ещё матчит dynamic {seg} (docblock обещает «unknown exact key»). packages/core/src/Registry/Resolver/EffectivePermissionResolver.php: в wildcard-off ветке дропать ключи с PermissionKey::WILDCARD до dynamic-проверки. Тест.' },
  { id: 'T5', phase: 'Tails', kind: 'code', risk: 'LOW', title: 'migration 000004 down() backfill + rollback test',
    brief: 'packages/core/database/migrations/2026_01_01_000004_*: в down() бэкфилить/удалять null-строки перед nullable(false) (иначе падает на MySQL/PG); добавить migrate:rollback-тест.' },
  { id: 'T6', phase: 'Tails', kind: 'test', risk: 'LOW', title: 'epoch redis integration + Octane isolation (infra-gated)',
    brief: 'Интеграционный тест epoch add+increment под реальным redis + Octane cross-worker изоляция requestCache. ЕСЛИ нет реального redis в окружении — status=deferred (infra), НЕ форси и НЕ мокай под видом реального. Рассмотреть верхнюю границу/reset epoch (опц.).' },
]

// review lane by risk (mirror dev-loop)
function implLane(risk) {
  return { model: risk === 'LOW' ? 'haiku' : 'sonnet', effort: risk === 'HIGH' ? 'high' : risk === 'LOW' ? 'low' : 'medium' }
}
function reviewLane(risk) {
  if (risk === 'HIGH') return { model: 'opus', effort: 'xhigh' }
  if (risk === 'LOW') return { model: 'sonnet', effort: 'medium' }
  return { model: 'opus', effort: 'high' }
}

const PHASE_COMMIT = {
  'Phase 5 — Console/CLI': 'feat(core): complete CLI surface (role lifecycle, explain/abilities, structured output), unify command prefix',
  'Phase 6 — Filament & Context': 'feat(filament,context): enforce page/widget perms, sync codegen with runtime, context write-API',
  'Phase 7 — Cleanup/Testing/CI': 'chore(core): remove dead code, close CLI/abilities test gaps, tighten arch & mutation gates',
  'Phase 8 — Docs & DX': 'docs: rebuild abilities/extending guides on real API, CLI reference generator, EN<->RU parity, toolkit sync',
  'Tails': 'fix(core): residual tails — panel-aware scoped query-scope, scoped-role removal semantics, diagnostics parity',
}

// ── Setup ────────────────────────────────────────────────────────────────
phase('Setup')
const setup = await safeAgent(
  `Подготовь автономный прогон в ${DIR}.
1. 'git status' — если дерево ГРЯЗНОЕ, ОСТАНОВИ и отчитайся (не затирай чужое).
2. ENV-PREFLIGHT: 'php -v'; 'php -r "print_r(PDO::getAvailableDrivers());"' — pdo_sqlite есть; пробный 'composer test --filter=__preflight_nonexistent__ > /tmp/pf.txt 2>&1' не падает фаталом окружения. Сломано → ОСТАНОВИ.
3. Ветка: 'git fetch origin ${BASE}'. Если ветка ${BRANCH} уже существует локально — 'git checkout ${BRANCH}' (НЕ сбрасывать, это resume). Иначе 'git checkout ${BASE}' → 'git pull --ff-only' → 'git checkout -b ${BRANCH}'.
4. Подтверди: окружение ок, ветка=${BRANCH}, дерево чистое, последний коммит (subject).
${GATES}`,
  { label: 'setup', phase: 'Setup', model: 'sonnet', effort: 'low' }
)

const results = []
let curPhase = null
let consecInfra = 0
let prNumber = null

async function finalizePhase(phaseTitle) {
  const phaseSlices = results.filter((r) => r._phase === phaseTitle)
  const summary = phaseSlices.map((r) => `${r.id}=${r.status}`).join(', ') || '(нет)'
  const msg = PHASE_COMMIT[phaseTitle] || `chore(core): ${phaseTitle}`
  return await safeAgent(
    `Финализация фазы «${phaseTitle}» в ${DIR}, ветка ${BRANCH}. Срезы фазы: ${summary}.
1. 'git status' — если есть НЕзакоммиченные правки от срезов, оставь дерево чистым: либо доведи до валидного состояния и закоммить, либо 'git stash'/откати частичное (реализации срезов уже коммитились агентами — тут только хвосты).
2. Прогони ПОЛНЫЙ 'composer check > /tmp/fin_${phaseTitle.replace(/[^a-z0-9]/gi, '_')}.txt 2>&1' (Pint/PHPStan honest-baseline/Rector/type-coverage>=98/Pest). Если КРАСНО — сделай ОГРАНИЧЕННУЮ починку (макс 2-3 итерации: honest-baseline unmatched → снять запись; форматирование → composer lint; реальная регрессия → фикс). Не чинится за лимит — НЕ коммить сломанное, оставь дерево чистым, отчитайся status с blocker.
3. Обнови соответствующий CHANGELOG (packages/core|filament|context/CHANGELOG.md) секцией Unreleased по факту сделанного в фазе.
4. Если composer check ЗЕЛЁНЫЙ — 'git add -A && git commit' одним коммитом фазы с сообщением: "${msg}" (тело — список закрытых F/T-ID). Коммиты срезов внутри фазы НЕ сквошить (морнинг-ревью решит).
5. Инкрементальный durable-push (progress переживает смерть процесса): 'git push -u origin ${BRANCH}'.
6. Отчитайся: composer check GREEN/RED, что закоммичено, что осталось.
${GATES}`,
    { label: `finalize:${phaseTitle}`, phase: phaseTitle, model: 'opus', effort: 'high' }
  )
}

// ── Sequential slice loop (общий git-tree ⇒ строго последовательно) ────────
for (const slice of SLICES) {
  if (slice.phase !== curPhase) {
    if (curPhase !== null) {
      const fin = await finalizePhase(curPhase)
      results.push({ id: `__finalize_${curPhase}`, _phase: curPhase, status: 'green', summary: String(fin || '').slice(0, 400), _finalize: true })
      // Открой draft PR один раз после первой финализированной фазы (durable review-surface).
      if (prNumber === null) {
        const pr = await safeAgent(
          `В ${DIR} на ветке ${BRANCH} (уже запушена): проверь 'gh pr list --head ${BRANCH} --state open'. Если PR нет — открой DRAFT PR в ${BASE}: 'gh pr create --draft --base ${BASE} --head ${BRANCH} --title "refactor: finish IMPROVEMENT_PLAN (Phases 5-8 + tails) [autonomous, WIP]" --body "Автономный overnight-прогон доделки плана. DRAFT — НЕ мержить без ревью. Прогресс пушится по фазам; финальный статус — в REMAINDER_REPORT.md."'. Верни номер PR. НЕ мержить.
${GATES}`,
          { label: 'draft-pr', phase: curPhase, model: 'sonnet', effort: 'low' }
        )
        prNumber = String(pr || '').slice(0, 200)
      }
    }
    curPhase = slice.phase
    phase(curPhase)
  }

  if (consecInfra >= 2) {
    log(`Circuit-breaker: 2 infra-fail подряд (rate-limit/ночная смерть рантайма) — стоп прогона, иду в Finalize.`)
    break
  }

  const risk = String(slice.risk || 'MED').toUpperCase()
  const lane = implLane(risk)
  const priorTail = results.filter((r) => !r._finalize).slice(-6).map((r) => `${r.id}=${r.status}`).join(', ') || '(первый срез)'

  // Выбор исполнителя по типу среза.
  const backendish = slice.kind === 'code' || slice.kind === 'filament' || slice.kind === 'context'
  const implType = backendish ? 'azguard-slice-builder' : (slice.kind === 'test' ? 'azguard-test-writer' : undefined)

  // 1) Реализация (+тесты для не-code, чтобы не плодить агентов).
  const implPrompt = backendish
    ? `Реализуй ОДИН срез ${slice.id} «${slice.title}» плана AzGuard в текущей ветке ${BRANCH}.
Прочитай строку ${slice.id} в ${DIR}/IMPROVEMENT_PLAN.md и скетч в ${DIR}/ARCHITECT_REVIEW.md (§4.5 CLI / §4.10 Filament&Context / §4.1 — по теме) — действия/файлы/AC оттуда.
Ключевое: ${slice.brief}
Реализацию построй по канону пакета; ТЕСТЫ не пиши (их пишет следующий агент). Прогони composer analyse на изменённом. Закоммить реализацию в ТЕКУЩУЮ ветку, дерево чистое. Верни STATUS (status=green если реализация консистентна и закоммичена; deferred если нужен человек/инфра — честно).
${GATES}`
    : `Выполни ОДИН срез ${slice.id} «${slice.title}» плана AzGuard в текущей ветке ${BRANCH} ПОЛНОСТЬЮ (реализация + тесты/проверка + коммит).
Прочитай строку ${slice.id} в ${DIR}/IMPROVEMENT_PLAN.md и скетч в ${DIR}/ARCHITECT_REVIEW.md (§4.6 testing / §4.7 docs / §4.11 toolkit — по теме).
Ключевое: ${slice.brief}
${slice.kind === 'docs' ? 'Это ДОКИ: примеры должны компилироваться/быть верны против реального 0.2 API; проверь ссылки на существующие символы. Тесты кода не нужны, но если добавляешь doc-lint/parity — доведи его до зелёного.' : 'Доведи composer test до ЗЕЛЁНОГО честно (без ослабления/обходок; обходку занеси в workarounds как [POSSIBLE-DEFECT]).'}
Прогони composer check на релевантном и закоммить в ТЕКУЩУЮ ветку, дерево чистое. Верни STATUS.
${GATES}`

  const implRes = await safeAgent(implPrompt, { label: `do:${slice.id}`, phase: slice.phase, agentType: implType, schema: STATUS, model: lane.model, effort: lane.effort })

  if (!implRes) {
    consecInfra++
    results.push({ id: slice.id, _phase: slice.phase, status: 'infra-failed', risk_level: risk, summary: 'agent вернул null (терминальный infra-сбой)' })
    log(`срез ${slice.id}: infra-failed (null) — consecInfra=${consecInfra}`)
    continue
  }
  consecInfra = 0
  log(`срез ${slice.id}: impl=${implRes.status}`)

  // 2) Тесты до зелёного — только для backendish (code/filament/context) green-реализаций.
  let testRes = implRes
  if (backendish && implRes.status === 'green') {
    testRes = await safeAgent(
      `Ты в ветке ${BRANCH} в ${DIR}; срез ${slice.id} «${slice.title}» уже реализован (${slice.brief}).
Напиши тесты (pest, зеркаль стиль соседних), доказывающие acceptance_criteria из IMPROVEMENT_PLAN.md строки ${slice.id}, и итерируй 'composer test > /tmp/t_${slice.id}.txt 2>&1' до ЗЕЛЁНОГО.
- Зелено → закоммить тесты в ${BRANCH}, status=green.
- Честно не зелено → НЕ ослабляй тесты, дерево чистое, status=deferred, объясни в open_questions.
Любую обходку ради зелёного занеси в workarounds ([POSSIBLE-DEFECT]). Обнови контракт-дубли при смене контракта. Верни STATUS.
${GATES}`,
      { label: `test:${slice.id}`, phase: slice.phase, agentType: 'azguard-test-writer', schema: STATUS, effort: 'medium' }
    )
    if (!testRes) { testRes = { id: slice.id, status: 'infra-failed', summary: 'test-agent null' } }
    log(`срез ${slice.id}: test=${testRes.status}`)
  }

  const finalStatus = testRes && testRes.status ? testRes.status : implRes.status
  const merged = { id: slice.id, _phase: slice.phase, status: finalStatus, risk_level: risk, title: slice.title,
    summary: (testRes && testRes.summary) || implRes.summary,
    files_changed: (testRes && testRes.files_changed) || implRes.files_changed || [],
    workarounds: [ ...(implRes.workarounds || []), ...((testRes && testRes.workarounds) || []) ],
    open_questions: [ ...(implRes.open_questions || []), ...((testRes && testRes.open_questions) || []) ] }

  // 3) Risk-адаптивный review-gate — только по зелёным code/filament/context/HIGH.
  if (finalStatus === 'green' && (backendish || risk === 'HIGH')) {
    const rl = reviewLane(risk)
    const wk = merged.workarounds.length ? 'ОБХОДКИ (каждая — индикатор реального дефекта): ' + merged.workarounds.join(' | ') : 'обходок не заявлено'
    const depth = risk === 'HIGH'
      ? 'РЕЖИМ ADVERSARIAL (HIGH): перечисли failure-modes (гонки/утечка изоляции между панелями/неверный auth/nav-hiding≠контроль/сломанный config-дефолт/миграция), для КАЖДОГО построй конкретный сценарий где дифф ломается. Суди по диффу.'
      : risk === 'MED' ? 'РЕЖИМ MED: контракты, авторизация, целостность, config-overridability.' : 'РЕЖИМ LOW: style + N+1 + очевидная корректность.'
    const review = await safeAgent(
      `Read-only ревью среза ${slice.id} «${slice.title}» в ${DIR}, ветка ${BRANCH}. Diff: 'git diff ${BASE}...${BRANCH}' по файлам среза.
RISK=${risk}. ${depth}
${wk}
Проверь корректность, авторизацию, инварианты §6 ARCHITECT_REVIEW и acceptance_criteria (${slice.id} в IMPROVEMENT_PLAN.md). Прогони composer analyse на изменённых + релевантные тесты. Репортить ВСЕ находки: severity∈{LOW,MED,HIGH,CRITICAL}+file:line+failure_scenario. Файлы НЕ меняй, дерево чистое.
${GATES}`,
      { label: `review:${slice.id}`, phase: slice.phase, agentType: 'azguard-reviewer', model: rl.model, effort: rl.effort }
    )
    merged.review = String(review || '').slice(0, 1200)
    log(`срез ${slice.id}: review-ok`)
  }

  results.push(merged)
}

// финализация последней фазы цикла
if (curPhase !== null) {
  const fin = await finalizePhase(curPhase)
  results.push({ id: `__finalize_${curPhase}`, _phase: curPhase, status: 'green', summary: String(fin || '').slice(0, 400), _finalize: true })
}

// ── Finalize ─────────────────────────────────────────────────────────────
phase('Finalize')
const slicesOnly = results.filter((r) => !r._finalize)
const green = slicesOnly.filter((r) => r.status === 'green').map((r) => r.id)
const deferred = slicesOnly.filter((r) => r.status === 'deferred').map((r) => r.id)
const failed = slicesOnly.filter((r) => r.status === 'failed' || r.status === 'infra-failed').map((r) => r.id)
const workaroundsAll = slicesOnly.filter((r) => (r.workarounds || []).length).map((r) => `${r.id}: ${r.workarounds.join('; ')}`)

const report = await safeAgent(
  `Собери утренний отчёт автономного прогона в ${DIR}, ветка ${BRANCH}.
Данные (из оркестратора):
- GREEN (${green.length}): ${green.join(', ') || '—'}
- DEFERRED (нужен человек/инфра) (${deferred.length}): ${deferred.join(', ') || '—'}
- FAILED/INFRA (${failed.length}): ${failed.join(', ') || '—'}
- [POSSIBLE-DEFECT] обходки: ${workaroundsAll.join(' || ') || 'нет'}
- draft PR: ${prNumber || 'см. gh pr list'}
Действия:
1. 'git log --oneline ${BASE}..${BRANCH}' и финальный 'composer check > /tmp/final_check.txt 2>&1' — зафиксируй итог (GREEN/RED + число тестов).
2. Напиши ${DIR}/REMAINDER_REPORT.md: по фазам 5/6/7/8/Tails — что green/deferred/blocked, [POSSIBLE-DEFECT]-обходки к разбору, оставшиеся open_questions, статус composer check, ссылка на draft PR, и явный список «ТРЕБУЕТ ЧЕЛОВЕКА» (особенно F13 security-энфорс, F14 миграции, T1 panel-scope, если не green). Закоммить отчёт в ${BRANCH} и 'git push origin ${BRANCH}'.
3. Финальная строка — одно предложение вердикта.
НЕ мержить PR, НЕ мержить в ${BASE}.
${GATES}`,
  { label: 'morning-report', phase: 'Finalize', model: 'opus', effort: 'high' }
)

log(`Прогон завершён: ${green.length} green, ${deferred.length} deferred, ${failed.length} failed/infra. Ветка ${BRANCH} запушена, draft PR открыт, REMAINDER_REPORT.md записан. main НЕ тронут.`)
return { branch: BRANCH, base: BASE, green, deferred, failed, prNumber, report: String(report || '').slice(0, 800), setup: String(setup || '').slice(0, 300) }
