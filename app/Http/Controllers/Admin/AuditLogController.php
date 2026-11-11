<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Tra cứu nhật ký hệ thống (K8) - chỉ Admin. Chỉ đọc: không có thao tác sửa hay xóa. */
class AuditLogController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return view('admin.audit.index', [
            'logs' => $this->audit->search($filters),
            'actions' => $this->audit->actions(),
            'filters' => $filters,
        ]);
    }
}
