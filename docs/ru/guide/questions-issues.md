# Вопросы и проблемы

## Поддержка

- **GitHub Issues** — для баг-репортов и запросов функций: [github.com/axioma-studio/azguard-private/issues](https://github.com/axioma-studio/azguard-private/issues)
- **Discussions** — для вопросов по использованию: [github.com/axioma-studio/azguard-private/discussions](https://github.com/axioma-studio/azguard-private/discussions)

## Перед тем как открыть issue

1. Запустите `php artisan azguard:doctor` — он диагностирует большинство типичных проблем
2. Проверьте раздел [Исключения](/ru/guide/exceptions)
3. Убедитесь, что трейт `HasAzGuard` подключён к модели
4. Убедитесь, что миграции выполнены: `php artisan migrate:status`

## Шаблон баг-репорта

При создании issue укажите:
- Версию AzGuard (`composer show axioma-studio/azguard`)
- Версию PHP и Laravel
- Минимальный воспроизводимый пример
- Полный стек ошибки
