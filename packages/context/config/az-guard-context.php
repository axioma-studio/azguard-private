<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Merge Strategy
    |--------------------------------------------------------------------------
    |
    | Определяет, как объединяются глобальные права и контекстные права.
    |
    | Встроенные стратегии:
    |   GlobalPlusContextStrategy  — global ∪ context (дефолт)
    |   ContextOnlyStrategy        — только context, global игнорируется
    |   DenyWithoutContextStrategy — пустой set без контекста
    |
    | Можно подставить свой класс, реализующий ContextMergeStrategy.
    |
    */
    'merge_strategy' => \AzGuard\Context\Strategies\GlobalPlusContextStrategy::class,

    /*
    |--------------------------------------------------------------------------
    | Context Resolvers
    |--------------------------------------------------------------------------
    |
    | Список FQCN классов, реализующих ResolvesContext.
    | Каждый resolver извлекает AuthorizationContext из Request
    | и устанавливает его в AuthorizationContextManager.
    |
    | Пример:
    |   'resolvers' => [
    |       App\AzGuard\WorkspaceContextResolver::class,
    |   ],
    |
    */
    'resolvers' => [],
];
