# Frontend Abilities

Abilities DTOs carry computed boolean flags from your policies to the front end. They are the answer to "what can this user do with *this specific resource right now*?"

## Why DTOs, not raw Gate calls on the front end?

Sending `abilities: { canEdit: true, canDelete: false }` in Inertia props is explicit, cacheable, and testable. The alternative — calling the API on every UI interaction — is slower and harder to reason about.

## Creating an Abilities DTO

```php
namespace App\Guards\App\Documents\Abilities;

use AzGuard\Concerns\ResolvesGateAbilities;
use App\Models\Documents\Document;
use App\Guards\App\Permissions\DocumentsPermission;

final class DocumentsAbilities
{
    use ResolvesGateAbilities;

    public function __construct(
        public readonly bool $canView,
        public readonly bool $canEdit,
        public readonly bool $canDelete,
    ) {}

    public static function fromDocument(Document $document): self
    {
        return new self(
            canView:   Gate::allows('app.documents.view',   $document),
            canEdit:   Gate::allows('app.documents.edit',   $document),
            canDelete: Gate::allows('app.documents.delete', $document),
        );
    }

    public function toArray(): array
    {
        return [
            'canView'   => $this->canView,
            'canEdit'   => $this->canEdit,
            'canDelete' => $this->canDelete,
        ];
    }
}
```

## Passing to Inertia

Pass abilities at the **page level**, not in global shared data. Abilities are resource-specific.

```php
// DocumentController@show
public function show(Document $document): Response
{
    return Inertia::render('Documents/Show', [
        'document'  => DocumentResource::make($document),
        'abilities' => DocumentsAbilities::fromDocument($document)->toArray(),
    ]);
}
```

## Consuming in Vue / React

```vue
<script setup>
const props = defineProps<{
  document: Document
  abilities: { canEdit: boolean; canDelete: boolean; canView: boolean }
}>()
</script>

<template>
  <button v-if="abilities.canEdit" @click="edit">Edit</button>
  <button v-if="abilities.canDelete" @click="destroy">Delete</button>
</template>
```

## Rules

- Abilities DTO never duplicates policy logic — all checks go through `Gate::allows()`.
- Pass abilities per page, not globally. Global shared props grow unbounded.
- DTO fields are `readonly` — computed once, never mutated.
