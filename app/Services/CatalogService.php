<?php

namespace App\Services;

use App\Repositories\CatalogRepository;
use App\Support\CatalogRegistry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/** Quản trị danh mục dùng chung (phòng ban, danh mục tài sản, danh mục sự cố). */
class CatalogService
{
    public function __construct(
        private readonly CatalogRepository $catalog,
        private readonly AuditService $audit,
    ) {
    }

    public function list(string $type): Collection
    {
        $def = CatalogRegistry::get($type);

        return $this->catalog->all($def['model'], $def['usage']);
    }

    public function find(string $type, int $id): Model
    {
        return $this->catalog->find(CatalogRegistry::get($type)['model'], $id);
    }

    /** Quy tắc kiểm tra dữ liệu vào theo từng danh mục (phía máy chủ). */
    public function rules(string $type, ?Model $editing = null): array
    {
        $def = CatalogRegistry::get($type);

        $rules = [
            'name' => ['required', 'string', 'max:100', Rule::unique($def['table'], 'name')->ignore($editing?->getKey())],
            'description' => ['nullable', 'string', 'max:255'],
        ];

        if (isset($def['boolean'])) {
            $rules[$def['boolean']] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    public function create(string $type, array $data): Model
    {
        $item = $this->catalog->create(CatalogRegistry::get($type)['model'], $this->normalize($type, $data));
        $this->audit->log("catalog.{$type}.created", null, $item, ['name' => $item->name]);

        return $item;
    }

    public function update(string $type, Model $item, array $data): void
    {
        $this->catalog->update($item, $this->normalize($type, $data));
        $this->audit->log("catalog.{$type}.updated", null, $item, ['name' => $item->name]);
    }

    /** Không xóa mục đang được sử dụng (giữ toàn vẹn tham chiếu). */
    public function delete(string $type, Model $item): bool
    {
        $def = CatalogRegistry::get($type);

        if ($this->catalog->usageCount($item, $def['usage']) > 0) {
            return false;
        }

        $this->catalog->delete($item);
        $this->audit->log("catalog.{$type}.deleted", null, null, ['id' => $item->getKey(), 'name' => $item->name]);

        return true;
    }

    private function normalize(string $type, array $data): array
    {
        $def = CatalogRegistry::get($type);

        if (isset($def['boolean'])) {
            $data[$def['boolean']] = (bool) ($data[$def['boolean']] ?? false);
        }

        return $data;
    }
}
