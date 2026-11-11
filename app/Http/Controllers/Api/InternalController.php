<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SlaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Điểm cuối nội bộ cho trình lập lịch bên ngoài (hosting không có cron). Bảo vệ bằng khóa bí mật so sánh hằng thời gian. */
class InternalController extends Controller
{
    public function escalate(Request $request, SlaService $sla): JsonResponse
    {
        $expected = (string) config('sla.cron_key');

        // Chưa cấu hình khóa => coi như không tồn tại (không lộ điểm cuối).
        abort_if($expected === '', 404);

        if (! hash_equals($expected, (string) $request->header('X-Cron-Key'))) {
            return response()->json(['success' => false, 'message' => 'Khóa không hợp lệ.'], 403);
        }

        return response()->json(['success' => true, 'message' => 'Đã quét SLA.', 'data' => ['escalated' => $sla->escalateOverdue()]]);
    }
}
