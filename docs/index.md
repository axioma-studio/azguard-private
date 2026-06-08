---
layout: home

hero:
  name: AzGuard
  text: Code-first RBAC for Laravel
  tagline: Roles as PHP classes. Permissions in Git. Zero magic.
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: Why AzGuard?
      link: /guide/why-azguard
    - theme: alt
      text: View on GitHub
      link: https://github.com/axioma-studio/azguard-private

features:
  - icon: 🏗️
    title: Code-First Roles
    details: Define roles as PHP classes with typed permission arrays. No more database migrations for every permission change.

  - icon: 🔒
    title: Multi-Panel Support
    details: Isolated permission scopes per panel (app, admin, api). One user, different access contexts.

  - icon: 🎯
    title: Entity Scopes
    details: Assign roles per entity — user is Editor on Project A but not Project B. First-class support out of the box.

  - icon: ⚡
    title: Laravel Gate Native
    details: Plugs directly into Laravel Gate via Gate::before(). Works with @can, Gate::allows(), policies — no new API to learn.

  - icon: 🐘
    title: PHP 8.3+ Attributes
    details: Use #[RequiresPermission] and #[RequiresRole] attributes on controllers. Declarative, readable, IDE-friendly.

  - icon: 📦
    title: Composer-Ready
    details: Install via Composer, publish config, run migrations. Works with Laravel 11 and 12.
---
