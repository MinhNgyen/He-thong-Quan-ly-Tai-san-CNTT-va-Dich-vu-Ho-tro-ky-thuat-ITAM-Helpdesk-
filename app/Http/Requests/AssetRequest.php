<?php

namespace App\Http\Requests;

use App\Enums\AssetStatus;
use Illuminate\Foundation\Http\FormRequest;
use App\Services\AssetService;
use Illuminate\Validation\Rule;

/**
 * Dùng chung cho thêm và sửa tài sản. Quyền truy cập chức năng do middleware `role:` ở routes đảm nhiệm.
 */
class AssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Khi sửa, route có tham số {asset}; bỏ qua chính bản ghi này khi kiểm tra unique.
        $ignoreId = $this->route('asset')?->id;

        return [
            'asset_code' => ['required', 'string', 'max:50', Rule::unique('assets', 'asset_code')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'integer', 'exists:asset_categories,id'],
            'serial_number' => ['nullable', 'string', 'max:100', Rule::unique('assets', 'serial_number')->ignore($ignoreId)],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'status' => ['required', Rule::in(AssetStatus::values())],
            'description' => ['nullable', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:assets,id', function (string $attribute, mixed $value, \Closure $fail) {
                $asset = $this->route('asset');

                if ($asset && $value !== null && app(AssetService::class)->wouldCreateCycle($asset, (int) $value)) {
                    $fail('Tài sản cha không được là chính nó hoặc một tài sản con cháu của nó.');
                }
            }],
        ];
    }
}
