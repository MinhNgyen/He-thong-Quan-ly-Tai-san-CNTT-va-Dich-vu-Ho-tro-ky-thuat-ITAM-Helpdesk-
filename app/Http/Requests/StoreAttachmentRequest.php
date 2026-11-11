<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Cùng quyền với bình luận: xem được phiếu và phiếu chưa đóng/hủy.
        return $this->user()?->can('comment', $this->route('ticket')) ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Vui lòng chọn tệp cần đính kèm.',
            'file.file' => 'Tệp tải lên không hợp lệ.',
            'file.max' => 'Tệp không được vượt quá 5 MB.',
        ];
    }
}
