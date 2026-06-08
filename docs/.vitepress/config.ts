import { defineConfig } from 'vitepress'

export default defineConfig({
  lang: 'en-US',
  title: 'AzGuard',
  description: 'Code-first RBAC for Laravel — roles as PHP classes, permissions in Git.',

  base: '/azguard-private/',

  head: [
    ['link', { rel: 'icon', href: '/azguard-private/favicon.ico' }],
  ],

  themeConfig: {
    logo: null,
    siteTitle: 'AzGuard',

    nav: [
      { text: 'Guide', link: '/guide/quick-start' },
      { text: 'Reference', link: '/guide/artisan-commands' },
      { text: 'GitHub', link: 'https://github.com/axioma-studio/azguard-private' },
    ],

    sidebar: [
      {
        text: 'Introduction',
        collapsed: false,
        items: [
          { text: 'What is AzGuard?', link: '/guide/why-azguard' },
          { text: 'Quick Start',      link: '/guide/quick-start' },
        ],
      },
      {
        text: 'Core Concepts',
        collapsed: false,
        items: [
          { text: 'Panels',              link: '/guide/panels' },
          { text: 'Permissions',         link: '/guide/permissions' },
          { text: 'Roles',               link: '/guide/roles' },
          { text: 'Policies & Gates',    link: '/guide/policies-and-gates' },
          { text: 'Permission Catalog',  link: '/guide/permission-catalog' },
        ],
      },
      {
        text: 'Features',
        collapsed: false,
        items: [
          { text: 'HTTP Access',        link: '/guide/http-access' },
          { text: 'Direct Grants',      link: '/guide/direct-grants' },
          { text: 'Entity Scopes',      link: '/guide/entity-scopes' },
          { text: 'Frontend Abilities', link: '/guide/abilities-frontend' },
          { text: 'Context (opt-in)',   link: '/guide/context' },
        ],
      },
      {
        text: 'Integrations',
        collapsed: false,
        items: [
          { text: 'Filament',  link: '/guide/filament' },
        ],
      },
      {
        text: 'Reference',
        collapsed: false,
        items: [
          { text: 'Artisan Commands', link: '/guide/artisan-commands' },
          { text: 'Configuration',    link: '/guide/configuration' },
          { text: 'Comparison',       link: '/guide/comparison' },
          { text: 'Changelog',        link: '/guide/changelog' },
        ],
      },
      {
        text: 'Recipes',
        collapsed: true,
        items: [
          { text: 'Soft Role Override',       link: '/guide/recipes/soft-role-override' },
          { text: 'Super-admin Wildcard',     link: '/guide/recipes/super-admin-wildcard' },
          { text: 'Temp Access via Grant',    link: '/guide/recipes/temp-access-via-grant' },
        ],
      },
    ],

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
