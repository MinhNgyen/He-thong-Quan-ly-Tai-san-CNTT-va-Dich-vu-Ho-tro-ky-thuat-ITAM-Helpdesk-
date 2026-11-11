<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** K7: báo cáo tổng hợp (JOIN nhiều bảng + hàm tổng hợp + điều kiện thời gian) và xuất CSV. */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function seedData(): void
    {
        $employee = User::factory()->create();
        $tech = User::factory()->technician()->create(['name' => 'Kỹ thuật A']);
        $cat = TicketCategory::create(['name' => 'Mạng', 'description' => 'm', 'is_active' => true]);

        $created = now()->subDays(2);
        foreach ([['resolved', 3], ['resolved', 5], ['open', null]] as $i => [$status, $hours]) {
            $ticket = Ticket::create([
                'ticket_code' => "HD-R-{$i}", 'created_by' => $employee->id, 'assigned_to' => $status === 'open' ? null : $tech->id,
                'category_id' => $cat->id, 'title' => "T{$i}", 'description' => 'd', 'priority' => 'high', 'status' => $status,
                'resolved_at' => $hours ? (clone $created)->addHours($hours) : null,
                'sla_due_at' => (clone $created)->addHours(4),
            ]);
            // created_at không nằm trong danh sách fillable: đặt trực tiếp trong CSDL để mô phỏng phiếu cũ.
            \Illuminate\Support\Facades\DB::table('tickets')->where('id', $ticket->id)->update(['created_at' => $created, 'updated_at' => $created]);
        }

        $assetCat = AssetCategory::create(['name' => 'Laptop', 'description' => 'l']);
        Asset::create(['asset_code' => 'A1', 'name' => 'L1', 'category_id' => $assetCat->id, 'status' => 'available', 'purchase_price' => 1000]);
        Asset::create(['asset_code' => 'A2', 'name' => 'L2', 'category_id' => $assetCat->id, 'status' => 'in_use', 'purchase_price' => 2500]);
    }

    public function test_only_admin_can_open_reports(): void
    {
        $this->actingAs(User::factory()->create())->get('/reports')->assertForbidden();
        $this->actingAs(User::factory()->technician()->create())->get('/reports')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/reports')->assertOk();
    }

    public function test_reports_page_shows_aggregated_numbers(): void
    {
        $this->seedData();

        $this->actingAs(User::factory()->admin()->create())->get('/reports')
            ->assertOk()
            ->assertSee('Phiếu hỗ trợ theo tháng')
            ->assertSee('Tuân thủ SLA theo mức ưu tiên')
            ->assertSee('Mạng')          // danh mục (JOIN ticket_categories)
            ->assertSee('Kỹ thuật A')    // kỹ thuật viên (JOIN users/roles)
            ->assertSee('Laptop');
    }

    public function test_resolution_time_average_is_computed_in_the_database(): void
    {
        $this->seedData();

        $rows = app(\App\Repositories\ReportRepository::class)->resolutionByCategory();

        $this->assertCount(1, $rows);
        $this->assertSame('Mạng', $rows[0]['category']);
        $this->assertEquals(3, $rows[0]['total']);
        $this->assertEquals(2, $rows[0]['resolved']);
        $this->assertEquals(4.0, (float) $rows[0]['avg_hours']); // (3h + 5h) / 2
    }

    public function test_sla_compliance_counts_met_and_late(): void
    {
        $this->seedData();

        $row = app(\App\Repositories\ReportRepository::class)->slaCompliance()[0];

        $this->assertSame('high', $row['priority']);
        $this->assertEquals(1, $row['met']);    // 3h <= 4h
        $this->assertEquals(1, $row['late']);   // 5h > 4h
        $this->assertEquals(1, $row['overdue']); // phiếu open đã quá hạn
    }

    public function test_csv_export_is_utf8_with_bom_and_audited(): void
    {
        $this->seedData();

        $response = $this->actingAs(User::factory()->admin()->create())->get('/reports/assets-by-category/export.csv');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('"Danh mục",Tổng,"Sẵn sàng"', $csv);
        $this->assertStringContainsString('Laptop,2,1,1,0,0,3500', $csv);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.exported']);
    }

    public function test_csv_neutralises_formula_injection_and_escapes_delimiters(): void
    {
        $employee = User::factory()->create();
        $evil = TicketCategory::create(['name' => '=HYPERLINK("http://x")', 'description' => 'x', 'is_active' => true]);
        $comma = TicketCategory::create(['name' => 'Mạng, thiết bị "lõi"', 'description' => 'x', 'is_active' => true]);
        foreach ([$evil, $comma] as $i => $c) {
            Ticket::create(['ticket_code' => "HD-X{$i}", 'created_by' => $employee->id, 'category_id' => $c->id, 'title' => 't', 'description' => 'd', 'priority' => 'low', 'status' => 'open']);
        }

        $csv = $this->actingAs(User::factory()->admin()->create())->get('/reports/resolution-by-category/export.csv')->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);          // không còn bắt đầu bằng "="
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
        $this->assertStringContainsString('"Mạng, thiết bị ""lõi"""', $csv); // dấu phẩy và nháy được escape đúng chuẩn CSV
    }

    public function test_unknown_report_is_404(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/reports/khong-ton-tai/export.csv')->assertNotFound();
    }
}
