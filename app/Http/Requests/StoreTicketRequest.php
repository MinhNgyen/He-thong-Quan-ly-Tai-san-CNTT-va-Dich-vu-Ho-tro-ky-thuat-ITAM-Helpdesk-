<?php

namespace App\Http\Requests;

use App\Enums\AssetStatus;
use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Mọi người dùng đã đăng nhập đều được tạo phiếu (middleware `auth` ở routes).
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('ticket_categories', 'id')->where('is_active', true)],
            // Chỉ cho gắn tài sản đang sẵn sàng / đang sử dụng, giống danh sách trên form.
            'asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')->whereIn('status', AssetStatus::assignable())],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:10000'],
            'priority' => ['required', Rule::in(TicketPriority::values())],
        ];
    }
}
