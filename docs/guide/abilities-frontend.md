# Abilities для фронта

Abilities DTO — readonly-класс с bool-полями, заполняется через `ResolvesGateAbilities::resolveGateFlags`.

```php
DocumentsAbilities::fromDocument($document)->toArray();
```

Передаётся в Inertia props **страницы**, не в глобальный layout.

Логика доступа — только в политиках; DTO не дублирует проверки.
