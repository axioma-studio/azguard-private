import { defineConfig } from 'vitepress'

// ─── Shared sidebar factories (EN) ───────────────────────────────────────────

function introSidebar(base = '') {
  const p = (s: string) => `${base}${s}`
  return [
    {
      text: 'Introduction',
      items: [
        { text: 'What is AzGuard?',   link: p('/guide/introduction/why-azguard') },
        { text: 'Prerequisites',      link: p('/guide/introduction/prerequisites') },
        { text: 'Installation',       link: p('/guide/introduction/installation') },
        { text: 'Quick Start',        link: p('/guide/introduction/quick-start') },
        { text: 'Upgrading',          link: p('/guide/introduction/upgrading') },
        { text: 'Questions & Issues', link: p('/guide/introduction/questions-issues') },
        { text: 'Changelog',          link: p('/guide/introduction/changelog') },
      ],
    },
  ]
}

function basicUsageSidebar(base = '') {
  const p = (s: string) => `${base}${s}`
  return [
    {
      text: 'Basic Usage',
      items: [
        { text: 'Overview',                 link: p('/guide/basic-usage/basic-usage') },
        { text: 'Permissions',              link: p('/guide/basic-usage/permissions') },
        { text: 'Roles',                    link: p('/guide/basic-usage/roles') },
        { text: 'Direct Grants',            link: p('/guide/basic-usage/direct-grants') },
        { text: 'Blade Directives',         link: p('/guide/basic-usage/blade-directives') },
        { text: 'Defining a Super-Admin',   link: p('/guide/basic-usage/super-admin') },
        { text: 'Multiple Guards',          link: p('/guide/basic-usage/multiple-guards') },
        { text: 'HTTP Access & Middleware', link: p('/guide/basic-usage/http-access') },
        { text: 'Artisan Commands',         link: p('/guide/basic-usage/artisan-commands') },
        { text: 'Filament Integration',     link: p('/guide/basic-usage/filament') },
        { text: 'Frontend Abilities',       link: p('/guide/basic-usage/abilities-frontend') },
      ],
    },
  ]
}

function bestPracticesSidebar(base = '') {
  const p = (s: string) => `${base}${s}`
  return [
    {
      text: 'Best Practices',
      items: [
        { text: 'Roles vs Permissions',   link: p('/guide/best-practices/best-practices') },
        { text: 'Model Policies & Gates', link: p('/guide/best-practices/policies-and-gates') },
        { text: 'Permission Catalog',     link: p('/guide/best-practices/permission-catalog') },
        { text: 'Performance Tips',       link: p('/guide/best-practices/performance-tips') },
      ],
    },
  ]
}

function advancedSidebar(base = '') {
  const p = (s: string) => `${base}${s}`
  return [
    {
      text: 'Advanced',
      items: [
        { text: 'Testing',          link: p('/guide/advanced/testing') },
        { text: 'Database Seeding', link: p('/guide/advanced/seeding') },
        { text: 'Exceptions',       link: p('/guide/advanced/exceptions') },
        { text: 'Extending',        link: p('/guide/advanced/extending') },
        { text: 'Cache',            link: p('/guide/advanced/cache') },
        { text: 'Events',           link: p('/guide/advanced/events') },
        { text: 'Context (opt-in)', link: p('/guide/advanced/context') },
        { text: 'Entity Scopes',    link: p('/guide/advanced/entity-scopes') },
        { text: 'Panels',           link: p('/guide/advanced/panels') },
        { text: 'UUID / ULID',      link: p('/guide/advanced/uuid-ulid') },
        { text: 'PhpStorm',         link: p('/guide/advanced/phpstorm') },
      ],
    },
    {
      text: 'Recipes',
      collapsed: true,
      items: [
        { text: 'Soft Role Override',    link: p('/guide/recipes/soft-role-override') },
        { text: 'Super-admin Wildcard',  link: p('/guide/recipes/super-admin-wildcard') },
        { text: 'Temp Access via Grant', link: p('/guide/recipes/temp-access-via-grant') },
        { text: 'Multi-Tenant Roles',    link: p('/guide/recipes/multi-tenant') },
        { text: 'Inertia Permissions',   link: p('/guide/recipes/inertia-permissions') },
        { text: 'Policy Integration',    link: p('/guide/recipes/policy-integration') },
      ],
    },
  ]
}

// ─── RU sidebar factories ────────────────────────────────────────────────────

function ruIntroSidebar() {
  const p = (s: string) => `/ru${s}`
  return [
    {
      text: 'Введение',
      items: [
        { text: 'Что такое AzGuard?',   link: p('/guide/introduction/why-azguard') },
        { text: 'Требования',           link: p('/guide/introduction/prerequisites') },
        { text: 'Установка',            link: p('/guide/introduction/installation') },
        { text: 'Быстрый старт',        link: p('/guide/introduction/quick-start') },
        { text: 'Обновление',           link: p('/guide/introduction/upgrading') },
        { text: 'Вопросы и проблемы',   link: p('/guide/introduction/questions-issues') },
        { text: 'Список изменений',     link: p('/guide/introduction/changelog') },
      ],
    },
  ]
}

function ruBasicUsageSidebar() {
  const p = (s: string) => `/ru${s}`
  return [
    {
      text: 'Основы',
      items: [
        { text: 'Обзор',                     link: p('/guide/basic-usage/basic-usage') },
        { text: 'Разрешения',                link: p('/guide/basic-usage/permissions') },
        { text: 'Роли',                      link: p('/guide/basic-usage/roles') },
        { text: 'Прямые гранты',             link: p('/guide/basic-usage/direct-grants') },
        { text: 'Blade-директивы',           link: p('/guide/basic-usage/blade-directives') },
        { text: 'Супер-администратор',       link: p('/guide/basic-usage/super-admin') },
        { text: 'Несколько Guards',          link: p('/guide/basic-usage/multiple-guards') },
        { text: 'HTTP и Middleware',         link: p('/guide/basic-usage/http-access') },
        { text: 'Artisan-команды',           link: p('/guide/basic-usage/artisan-commands') },
        { text: 'Интеграция с Filament',     link: p('/guide/basic-usage/filament') },
        { text: 'Права на фронтенде',        link: p('/guide/basic-usage/abilities-frontend') },
      ],
    },
  ]
}

function ruBestPracticesSidebar() {
  const p = (s: string) => `/ru${s}`
  return [
    {
      text: 'Лучшие практики',
      items: [
        { text: 'Роли vs Разрешения',    link: p('/guide/best-practices/best-practices') },
        { text: 'Политики и Gate',       link: p('/guide/best-practices/policies-and-gates') },
        { text: 'Каталог разрешений',    link: p('/guide/best-practices/permission-catalog') },
        { text: 'Производительность',    link: p('/guide/best-practices/performance-tips') },
      ],
    },
  ]
}

function ruAdvancedSidebar() {
  const p = (s: string) => `/ru${s}`
  return [
    {
      text: 'Продвинутое использование',
      items: [
        { text: 'Тестирование',         link: p('/guide/advanced/testing') },
        { text: 'Сидирование БД',       link: p('/guide/advanced/seeding') },
        { text: 'Исключения',           link: p('/guide/advanced/exceptions') },
        { text: 'Расширение',           link: p('/guide/advanced/extending') },
        { text: 'Кэш',                  link: p('/guide/advanced/cache') },
        { text: 'События',              link: p('/guide/advanced/events') },
        { text: 'Контекст (опц.)',       link: p('/guide/advanced/context') },
        { text: 'Entity Scopes',        link: p('/guide/advanced/entity-scopes') },
        { text: 'Панели',               link: p('/guide/advanced/panels') },
        { text: 'UUID / ULID',          link: p('/guide/advanced/uuid-ulid') },
        { text: 'PhpStorm',             link: p('/guide/advanced/phpstorm') },
      ],
    },
    {
      text: 'Рецепты',
      collapsed: true,
      items: [
        { text: 'Мягкое переопределение роли', link: p('/guide/recipes/soft-role-override') },
        { text: 'Супер-admin wildcard',        link: p('/guide/recipes/super-admin-wildcard') },
        { text: 'Временный доступ',            link: p('/guide/recipes/temp-access-via-grant') },
        { text: 'Multi-Tenant роли',           link: p('/guide/recipes/multi-tenant') },
        { text: 'Inertia + права',             link: p('/guide/recipes/inertia-permissions') },
        { text: 'Интеграция с Policy',         link: p('/guide/recipes/policy-integration') },
      ],
    },
  ]
}

// ─── Sidebar map helper ───────────────────────────────────────────────────────

function makeSidebarMap(base: string, factory: (b: string) => any[], pages: string[]) {
  return Object.fromEntries(pages.map(p => [`${base}${p}`, factory(base)]))
}

const introPages = [
  '/guide/introduction/why-azguard', '/guide/introduction/prerequisites', '/guide/introduction/installation',
  '/guide/introduction/quick-start', '/guide/introduction/upgrading', '/guide/introduction/questions-issues',
  '/guide/introduction/changelog', '/guide/introduction',
]
const basicPages = [
  '/guide/basic-usage/basic-usage', '/guide/basic-usage/permissions', '/guide/basic-usage/roles',
  '/guide/basic-usage/direct-grants', '/guide/basic-usage/blade-directives', '/guide/basic-usage/super-admin',
  '/guide/basic-usage/multiple-guards', '/guide/basic-usage/http-access', '/guide/basic-usage/artisan-commands',
  '/guide/basic-usage/filament', '/guide/basic-usage/abilities-frontend',
]
const bestPages = [
  '/guide/best-practices/best-practices', '/guide/best-practices/policies-and-gates',
  '/guide/best-practices/permission-catalog', '/guide/best-practices/performance-tips',
]
const advancedPages = [
  '/guide/advanced/testing', '/guide/advanced/seeding', '/guide/advanced/exceptions', '/guide/advanced/extending',
  '/guide/advanced/cache', '/guide/advanced/events', '/guide/advanced/context', '/guide/advanced/entity-scopes',
  '/guide/advanced/panels', '/guide/advanced/uuid-ulid', '/guide/advanced/phpstorm', '/guide/recipes',
]

// ─── Config ───────────────────────────────────────────────────────────────────

export default defineConfig({
  lang: 'en-US',
  title: 'AzGuard',
  description: 'Code-first RBAC for Laravel — roles as PHP classes, permissions in Git.',

  base: '/azguard/',

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/azguard/favicon.svg' }],
  ],

  locales: {
    root: {
      label: 'English',
      lang: 'en-US',
      link: '/',
    },
    ru: {
      label: 'Русский',
      lang: 'ru-RU',
      link: '/ru/',
      title: 'AzGuard',
      description: 'Code-first RBAC для Laravel — роли как PHP классы, права в Git.',
      themeConfig: {
        nav: [
          {
            text: 'Введение',
            link: '/ru/guide/introduction/why-azguard',
            activeMatch: '/ru/guide/(why-azguard|prerequisites|installation|quick-start|upgrading|questions-issues|changelog)',
          },
          {
            text: 'Основы',
            link: '/ru/guide/basic-usage/basic-usage',
            activeMatch: '/ru/guide/(basic-usage|permissions|roles|direct-grants|blade-directives|super-admin|multiple-guards|http-access|artisan-commands|filament|abilities-frontend)',
          },
          {
            text: 'Лучшие практики',
            link: '/ru/guide/best-practices/best-practices',
            activeMatch: '/ru/guide/(best-practices|policies-and-gates|permission-catalog|performance-tips)',
          },
          {
            text: 'Продвинутое',
            link: '/ru/guide/advanced/testing',
            activeMatch: '/ru/guide/(testing|seeding|exceptions|extending|cache|events|context|entity-scopes|panels|uuid-ulid|phpstorm|recipes)',
          },
          { text: 'GitHub', link: 'https://github.com/axioma-studio/azguard' },
        ],
        sidebar: {
          ...makeSidebarMap('/ru', ruIntroSidebar, introPages),
          ...makeSidebarMap('/ru', ruBasicUsageSidebar, basicPages),
          ...makeSidebarMap('/ru', ruBestPracticesSidebar, bestPages),
          ...makeSidebarMap('/ru', ruAdvancedSidebar, advancedPages),
        },
        outlineTitle: 'На этой странице',
        returnToTopLabel: 'Наверх',
        sidebarMenuLabel: 'Меню',
        darkModeSwitchLabel: 'Тема',
        langMenuLabel: 'Язык',
        editLink: {
          pattern: 'https://github.com/axioma-studio/azguard/edit/main/docs/:path',
          text: 'Редактировать на GitHub',
        },
        docFooter: {
          prev: 'Предыдущая',
          next: 'Следующая',
        },
      },
    },
  },

  themeConfig: {
    logo: '/logo.svg',
    siteTitle: 'AzGuard',

    nav: [
      {
        text: 'Introduction',
        link: '/guide/introduction/why-azguard',
        activeMatch: '/guide/(why-azguard|prerequisites|installation|quick-start|upgrading|questions-issues|changelog)',
      },
      {
        text: 'Basic Usage',
        link: '/guide/basic-usage/basic-usage',
        activeMatch: '/guide/(basic-usage|permissions|roles|direct-grants|blade-directives|super-admin|multiple-guards|http-access|artisan-commands|filament|abilities-frontend)',
      },
      {
        text: 'Best Practices',
        link: '/guide/best-practices/best-practices',
        activeMatch: '/guide/(best-practices|policies-and-gates|permission-catalog|performance-tips)',
      },
      {
        text: 'Advanced',
        link: '/guide/advanced/testing',
        activeMatch: '/guide/(testing|seeding|exceptions|extending|cache|events|context|entity-scopes|panels|uuid-ulid|phpstorm|recipes)',
      },
      { text: 'Changelog', link: '/guide/introduction/changelog' },
      { text: 'GitHub', link: 'https://github.com/axioma-studio/azguard' },
    ],

    sidebar: {
      ...makeSidebarMap('', introSidebar, introPages),
      ...makeSidebarMap('', basicUsageSidebar, basicPages),
      ...makeSidebarMap('', bestPracticesSidebar, bestPages),
      ...makeSidebarMap('', advancedSidebar, advancedPages),
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/axioma-studio/azguard' },
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2025-present Axioma Studio',
    },

    search: {
      provider: 'local',
    },

    editLink: {
      pattern: 'https://github.com/axioma-studio/azguard/edit/main/docs/:path',
      text: 'Edit this page on GitHub',
    },
  },
})
