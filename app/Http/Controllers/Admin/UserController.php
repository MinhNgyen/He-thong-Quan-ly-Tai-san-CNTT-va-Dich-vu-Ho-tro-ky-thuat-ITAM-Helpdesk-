<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserAdminRequest;
use App\Models\User;
use App\Services\UserAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserAdminService $users)
    {
    }

    public function index(Request $request): View
    {
        return view('admin.users.index', [
            'users' => $this->users->list($request->query('q'), $request->integer('role') ?: null),
            'roles' => $this->users->roles(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', $this->formData(new User(['is_active' => true]), false));
    }

    public function store(UserAdminRequest $request): RedirectResponse
    {
        $this->users->create($request->validated());

        return redirect()->route('admin.users.index')->with('success', 'Đã tạo tài khoản.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', $this->formData($user, true));
    }

    public function update(UserAdminRequest $request, User $user): RedirectResponse
    {
        $this->users->update($request->user(), $user, $request->validated());

        return redirect()->route('admin.users.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    private function formData(User $user, bool $editing): array
    {
        return [
            'editUser' => $user,
            'editing' => $editing,
            'roles' => $this->users->roles(),
            'departments' => $this->users->departments(),
        ];
    }
}
