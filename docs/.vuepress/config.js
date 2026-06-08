module.exports = {
  title: 'AzGuard',
  description: 'Code-first RBAC for Laravel — roles as PHP classes, permissions in Git.',
  themeConfig: {
    nav: [
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'GitHub', link: 'https://github.com/axioma-studio/azguard-private' },
    ],
    sidebar: [
      {
        title: 'Introduction',
        collapsable: false,
        children: [
          '/guide/getting-started',
          '/guide/why-azguard',
          '/guide/comparison',
          '/guide/concept',
          '/guide/architecture',
        ],
      },
      {
        title: 'Essentials',
        collapsable: false,
        children: [
          '/guide/roles',
          '/guide/permissions',
          '/guide/policies-and-gates',
          '/guide/http-access',
          '/guide/panels-app-vs-admin',
        ],
      },
      {
        title: 'Advanced',
        collapsable: false,
        children: [
          '/guide/domain-structure',
          '/guide/abilities-frontend',
          '/guide/filament',
          '/guide/recipes',
        ],
      },
    ],
  },
};
