<?php

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->ticket());
    }

    public function rules(): array
    {
        $rules = [
            'status' => ['required', Rule::in(TicketStatus::values())],
        ];

        // Chỉ người có quyền phân công mới được gửi assigned_to; với người khác trường này bị bỏ qua.
        if ($this->canAssign()) {
            $rules['assigned_to'] = ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)];
        }

        return $rules;
    }

    public function canAssign(): bool
    {
        return $this->user()->can('assign', $this->ticket());
    }

    private function ticket(): Ticket
    {
        return $this->route('ticket');
    }
}
