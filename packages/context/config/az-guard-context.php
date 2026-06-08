<?php

use AzGuard\Context\Strategies\GlobalPlusContextStrategy;

return [
    /*
    |--------------------------------------------------------------------------
    | Merge Strategy
    |--------------------------------------------------------------------------
    |
    | Определяет, как контекстные права объединяются с глобальными.
    |
    | Доступные стратегии:
    |   GlobalPlusContextStrategy  — глобальные + контекстные (default)
    |   ContextOnlyStrategy        — только контекстные права, глобальные игнорируются
    |   DenyWithoutContextStrategy — deny если контекст не установлен
    |
    | Можно указать любой класс, реализующий MergeStrategy.
    */
    'merge_strategy' => GlobalPlusContextStrategy::class,

    /*
    |--------------------------------------------------------------------------
    | Context Resolver
    |--------------------------------------------------------------------------
    |
    | FQCN класса, реализующего ResolvesContext.
    | Используется SetAuthorizationContext middleware для автоматического
    | определения контекста из запроса (например, из route parameter).
    |
    | Если null — контекст устанавливается вручную через AuthorizationContextManager.
    */
    'context_resolver' => null,
];
