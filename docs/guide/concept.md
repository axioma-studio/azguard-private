# Концепция AzGuard

AzGuard — слой авторизации на базе Laravel Gate с изоляцией по **панелям** (guard-модулям).

## Принципы

- **Типизация:** backed enum для permission, `final` классы, `declare(strict_types=1)`.
- **Атрибуты:** `#[GateAbility]`, `#[CheckPermission]`, `#[AzGuardPolicy]`.
- **Источник истины:** методы **политик**; роли хранят строки resolved-permission; `Gate::before` — только wildcard `*`.
- **Abilities:** DTO только для фронта (проекция `Gate::allows`), без дублирования бизнес-логики.

## Guard-модуль

Каталог `app/Guards/{Panel}/` — самодостаточный модуль: Roles, доменные подпапки с Permissions, Policies, Abilities.

## Именование permission

- В enum — **короткий ключ:** `documents.view`.
- В Gate и ролях — **с префиксом панели:** `app.documents.view` через `Panel::resolvePermission()`.

## Не входит в пакет

Модели приложения, Filament Resources, доменные правила доступа.
