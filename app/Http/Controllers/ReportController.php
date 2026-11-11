<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Báo cáo tổng hợp (K7): chỉ Admin. */
class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function index(): View
    {
        return view('reports.index', ['reports' => $this->reports->all()]);
    }

    public function export(string $report): StreamedResponse
    {
        abort_unless($this->reports->exists($report), 404);

        return $this->reports->exportCsv($report);
    }
}
