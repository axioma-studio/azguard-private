# Обновление

## С версии 1.x на 2.x

Обновите пакет через Composer:

```bash
composer require axioma-studio/azguard:^2.0
```

Затем опубликуйте и запустите новые миграции:

```bash
php artisan vendor:publish --tag=azguard-migrations --force
php artisan migrate
```

### Критические изменения 2.x

- Метод `assignRole()` теперь принимает FQCN класса роли вместо строки
- Конфиг `azguard.panels` обязателен — без него пакет выбросит `MissingPanelConfigException`
- `HasAzGuard::hasPermission()` возвращает `bool` (раньше мог вернуть `null`)

## Общий порядок обновления

1. Обновите зависимость в `composer.json`
2. Запустите `composer update axioma-studio/azguard`
3. Проверьте [Changelog](/ru/guide/changelog) на наличие критических изменений
4. Запустите `php artisan azguard:doctor` для диагностики
5. Запустите тесты
