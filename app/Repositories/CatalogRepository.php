<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CatalogRepository
{
    /** @param class-string<Model> $model */
    public function all(string $model, string $usageRelation): Collection
    {
        return $model::withCount($usageRelation)->orderBy('name')->get();
    }

    /** @param class-string<Model> $model */
    public function find(string $model, int $id): Model
    {
        return $model::findOrFail($id);
    }

    /** @param class-string<Model> $model */
    public function create(string $model, array $attributes): Model
    {
        return $model::create($attributes);
    }

    public function update(Model $item, array $attributes): bool
    {
        return $item->update($attributes);
    }

    public function delete(Model $item): void
    {
        $item->delete();
    }

    public function usageCount(Model $item, string $relation): int
    {
        return $item->{$relation}()->count();
    }
}
