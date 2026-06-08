import { defineConfig } from 'vitepress'

// ─── Sidebar factories ────────────────────────────────────────────────────────

function introSidebar() {
  return [
    {
      text: 'Introduction',
      items: [
        { text: 'What is AzGuard?',    link: '/guide/why-azguard' },
        { text: 'Prerequisites',       link: '/guide/prerequisites' },
        { text: 'Installation',        link: '/guide/installation' },
        { text: 'Quick Start',         link: '/guide/quick-start' },
        { text: 'Upgrading',           link: '/guide/upgrading' },
        { text: 'Questions & Issues',  link: '/guide/questions-issues' },
        { text: 'Changelog',           link: '/guide/changelog' },
      ],
    },
  ]
}

function basicUsageSidebar() {
  return [
    {
      text: 'Basic Usage',
      items: [
        { text: 'Overview',                   link: '/guide/basic-usage' },
        { text: 'Permissions',                link: '/guide/permissions' },
        { text: 'Roles',                      link: '/guide/roles' },
        { text: 'Direct Grants',              link: '/guide/direct-grants' },
        { text: 'Blade Directives',           link: '/guide/blade-directives' },
        { text: 'Defining a Super-Admin',     link: '/guide/super-admin' },
        { text: 'Multiple Guards',            link: '/guide/multiple-guards' },
        { text: 'HTTP Access & Middleware',   link: '/guide/http-access' },
        { text: 'Artisan Commands',           link: '/guide/artisan-commands' },
        { text: 'Filament Integration',       link: '/guide/filament' },
        { text: 'Frontend Abilities',         link: '/guide/abilities-frontend' },
      ],
    },
  ]
}

function bestPracticesSidebar() {
  return [
    {
      text: 'Best Practices',
      items: [
        { text: 'Roles vs Permissions',   link: '/guide/best-practices' },
        { text: 'Model Policies & Gates', link: '/guide/policies-and-gates' },
        { text: 'Permission Catalog',     link: '/guide/permission-catalog' },
        { text: 'Performance Tips',       link: '/guide/performance-tips' },
      ],
    },
  ]
}

function advancedSidebar() {
  return [
    {
      text: 'Advanced',
      items: [
        { text: 'Testing',           link: '/guide/testing' },
        { text: 'Database Seeding',  link: '/guide/seeding' },
        { text: 'Exceptions',        link: '/guide/exceptions' },
        { text: 'Extending',         link: '/guide/extending' },
        { text: 'Cache',             link: '/guide/cache' },
        { text: 'Events',            link: '/guide/events' },
        { text: 'Context (opt-in)',   link: '/guide/context' },
        { text: 'Entity Scopes',     link: '/guide/entity-scopes' },
        { text: 'Panels',            link: '/guide/panels' },
        { text: 'UUID / ULID',       link: '/guide/uuid-ulid' },
        { text: 'PhpStorm',          link: '/guide/phpstorm' },
      ],
    },
    {
      text: 'Recipes',
      collapsed: true,
      items: [
        { text: 'Soft Role Override',    link: '/guide/recipes/soft-role-override' },
        { text: 'Super-admin Wildcard',  link: '/guide/recipes/super-admin-wildcard' },
        { text: 'Temp Access via Grant', link: '/guide/recipes/temp-access-via-grant' },
        { text: 'Multi-Tenant Roles',    link: '/guide/recipes/multi-tenant' },
        { text: 'Inertia Permissions',   link: '/guide/recipes/inertia-permissions' },
        { text: 'Policy Integration',    link: '/guide/recipes/policy-integration' },
      ],
    },
  ]
}

// ─── Config ───────────────────────────────────────────────────────────────────

export default defineConfig({
  lang: 'en-US',
  title: 'AzGuard',
  description: 'Code-first RBAC for Laravel — roles as PHP classes, permissions in Git.',

  base: '/azguard-private/',

  head: [
    ['link', { rel: 'icon', href: '/azguard-private/favicon.ico' }],
  ],

  themeConfig: {
    logo: '/logo.png',
    siteTitle: 'AzGuard',

    // ─── Top nav ────────────────────────────────────────────────────────────
    nav: [
      {
        text: 'Introduction',
        link: '/guide/why-azguard',
        activeMatch: '/guide/(why-azguard|prerequisites|installation|quick-start|upgrading|questions-issues|changelog)',
      },
      {
        text: 'Basic Usage',
        link: '/guide/basic-usage',
        activeMatch: '/guide/(basic-usage|permissions|roles|direct-grants|blade-directives|super-admin|multiple-guards|http-access|artisan-commands|filament|abilities-frontend)',
      },
      {
        text: 'Best Practices',
        link: '/guide/best-practices',
        activeMatch: '/guide/(best-practices|policies-and-gates|permission-catalog|performance-tips)',
      },
      {
        text: 'Advanced',
        link: '/guide/testing',
        activeMatch: '/guide/(testing|seeding|exceptions|extending|cache|events|context|entity-scopes|panels|uuid-ulid|phpstorm|recipes)',
      },
      { text: 'Changelog', link: '/guide/changelog' },
      { text: 'GitHub', link: 'https://github.com/axioma-studio/azguard-private' },
    ],

    // ─── Sidebar ─────────────────────────────────────────────────────────────
    // VitePress matches the longest prefix — we map every guide/* page
    // to its section sidebar so the correct menu appears on every page.
    sidebar: {
      // Introduction
      '/guide/why-azguard':     introSidebar(),
      '/guide/prerequisites':   introSidebar(),
      '/guide/installation':    introSidebar(),
      '/guide/quick-start':     introSidebar(),
      '/guide/upgrading':       introSidebar(),
      '/guide/questions-issues': introSidebar(),
      '/guide/changelog':       introSidebar(),
      // catch-all for /guide/introduction redirect
      '/guide/introduction':    introSidebar(),

      // Basic Usage
      '/guide/basic-usage':        basicUsageSidebar(),
      '/guide/permissions':        basicUsageSidebar(),
      '/guide/roles':              basicUsageSidebar(),
      '/guide/direct-grants':      basicUsageSidebar(),
      '/guide/blade-directives':   basicUsageSidebar(),
      '/guide/super-admin':        basicUsageSidebar(),
      '/guide/multiple-guards':    basicUsageSidebar(),
      '/guide/http-access':        basicUsageSidebar(),
      '/guide/artisan-commands':   basicUsageSidebar(),
      '/guide/filament':           basicUsageSidebar(),
      '/guide/abilities-frontend': basicUsageSidebar(),

      // Best Practices
      '/guide/best-practices':     bestPracticesSidebar(),
      '/guide/policies-and-gates': bestPracticesSidebar(),
      '/guide/permission-catalog': bestPracticesSidebar(),
      '/guide/performance-tips':   bestPracticesSidebar(),

      // Advanced
      '/guide/testing':        advancedSidebar(),
      '/guide/seeding':        advancedSidebar(),
      '/guide/exceptions':     advancedSidebar(),
      '/guide/extending':      advancedSidebar(),
      '/guide/cache':          advancedSidebar(),
      '/guide/events':         advancedSidebar(),
      '/guide/context':        advancedSidebar(),
      '/guide/entity-scopes':  advancedSidebar(),
      '/guide/panels':         advancedSidebar(),
      '/guide/uuid-ulid':      advancedSidebar(),
      '/guide/phpstorm':       advancedSidebar(),
      '/guide/recipes':        advancedSidebar(),
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/axioma-studio/azguard-private' },
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2025-present Axioma Studio',
    },

    search: {
      provider: 'local',
    },

    editLink: {
      pattern: 'https://github.com/axioma-studio/azguard-private/edit/main/docs/:path',
      text: 'Edit this page on GitHub',
    },
  },
})
