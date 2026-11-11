<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ai xem được phiếu thì được bình luận (trừ phiếu đã đóng/hủy).
        return $this->user()->can('comment', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
        ];
    }
}
