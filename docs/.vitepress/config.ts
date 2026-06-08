import { defineConfig } from 'vitepress'

export default defineConfig({
  lang: 'en-US',
  title: 'AzGuard',
  description: 'Code-first RBAC for Laravel — roles as PHP classes, permissions in Git.',

  // Если репозиторий будет доступен как https://<org>.github.io/azguard/
  // раскомментировать и задать base:
  // base: '/azguard/',

  head: [
    ['link', { rel: 'icon', href: '/favicon.ico' }],
  ],

  themeConfig: {
    logo: '/logo.svg',
    siteTitle: 'AzGuard',

    nav: [
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'GitHub', link: 'https://github.com/axioma-studio/azguard-private' },
    ],

    sidebar: [
      {
        text: 'Introduction',
        collapsed: false,
        items: [
          { text: 'Getting Started', link: '/guide/getting-started' },
          { text: 'Why AzGuard?', link: '/guide/why-azguard' },
          { text: 'Comparison', link: '/guide/comparison' },
          { text: 'Concept', link: '/guide/concept' },
          { text: 'Architecture', link: '/guide/architecture' },
        ],
      },
      {
        text: 'Essentials',
        collapsed: false,
        items: [
          { text: 'Roles', link: '/guide/roles' },
          { text: 'Permissions', link: '/guide/permissions' },
          { text: 'Policies & Gates', link: '/guide/policies-and-gates' },
          { text: 'HTTP Access', link: '/guide/http-access' },
          { text: 'Panels: App vs Admin', link: '/guide/panels-app-vs-admin' },
        ],
      },
      {
        text: 'Advanced',
        collapsed: false,
        items: [
          { text: 'Entity Scopes', link: '/guide/entity-scopes' },
          { text: 'Domain Structure', link: '/guide/domain-structure' },
          { text: 'Frontend Abilities', link: '/guide/abilities-frontend' },
          { text: 'Filament Integration', link: '/guide/filament' },
          { text: 'Recipes', link: '/guide/recipes' },
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
