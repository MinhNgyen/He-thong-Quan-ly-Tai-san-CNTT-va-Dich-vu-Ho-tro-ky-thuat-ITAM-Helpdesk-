<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** K6, BM8: tải tệp an toàn, kiểm tra kiểu tệp thật, lưu ngoài thư mục public, tải xuống có kiểm quyền. */
class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = "\x89PNG\r\n\x1A\n\0\0\0\rIHDR\0\0\0\1\0\0\0\1\x08\x06\0\0\0\x1F\x15\xC4\x89";

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function ticketOf(User $owner): Ticket
    {
        $category = TicketCategory::firstOrCreate(['name' => 'HW'], ['description' => 'HW', 'is_active' => true]);

        return Ticket::create([
            'ticket_code' => 'HD-ATT-' . random_int(1000, 9999), 'created_by' => $owner->id, 'category_id' => $category->id,
            'title' => 'Có tệp', 'description' => 'Mô tả', 'priority' => 'medium', 'status' => 'open',
        ]);
    }

    private function upload(User $user, Ticket $ticket, UploadedFile $file)
    {
        return $this->actingAs($user)->post("/tickets/{$ticket->id}/attachments", ['file' => $file]);
    }

    public function test_owner_can_upload_a_real_png_and_it_is_stored_privately_with_random_name(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->ticketOf($owner);

        $this->upload($owner, $ticket, UploadedFile::fake()->createWithContent('Ảnh lỗi màn hình.png', self::PNG))->assertRedirect();

        $attachment = TicketAttachment::firstOrFail();
        $this->assertSame('image/png', $attachment->mime_type);
        $this->assertMatchesRegularExpression('/^[0-9a-f\-]{36}\.png$/', $attachment->stored_name);
        $this->assertSame(64, strlen($attachment->sha256));
        Storage::disk('local')->assertExists("attachments/{$ticket->id}/{$attachment->stored_name}");
        $this->assertDatabaseHas('audit_logs', ['action' => 'attachment.uploaded']);
    }

    public function test_script_disguised_as_image_is_rejected(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->ticketOf($owner);

        $this->upload($owner, $ticket, UploadedFile::fake()->createWithContent('shell.jpg', '<?php system($_GET["c"]); ?>'))
            ->assertSessionHasErrors('file');
        $this->upload($owner, $ticket, UploadedFile::fake()->createWithContent('run.php', '<?php echo 1;'))
            ->assertSessionHasErrors('file');
        $this->upload($owner, $ticket, UploadedFile::fake()->createWithContent('bin.pdf', "MZ\x90\0exe"))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('ticket_attachments', 0);
    }

    public function test_oversize_file_is_rejected(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->ticketOf($owner);

        $this->upload($owner, $ticket, UploadedFile::fake()->createWithContent('big.pdf', '%PDF-1.4' . str_repeat('a', 5 * 1024 * 1024 + 10)))
            ->assertSessionHasErrors('file');
    }

    public function test_other_employee_cannot_upload_or_download(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $ticket = $this->ticketOf($owner);

        $this->upload($owner, $ticket, UploadedFile::fake()->createWithContent('a.pdf', '%PDF-1.4 noi dung'))->assertRedirect();
        $attachment = TicketAttachment::firstOrFail();

        $this->upload($stranger, $ticket, UploadedFile::fake()->createWithContent('b.pdf', '%PDF-1.4 x'))->assertForbidden();
        $this->actingAs($stranger)->get("/attachments/{$attachment->id}")->assertForbidden();
        $this->actingAs($stranger)->delete("/attachments/{$attachment->id}")->assertForbidden();

        $response = $this->actingAs($owner)->get("/attachments/{$attachment->id}");
        $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_guest_cannot_download(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->ticketOf($owner);
        $this->upload($owner, $ticket, UploadedFile::fake()->createWithContent('a.pdf', '%PDF-1.4 noi dung'));

        auth()->logout();
        $this->get('/attachments/' . TicketAttachment::firstOrFail()->id)->assertRedirect('/login');
    }

    public function test_assigned_technician_can_download_and_uploader_can_delete(): void
    {
        $owner = User::factory()->create();
        $tech = User::factory()->technician()->create();
        $ticket = $this->ticketOf($owner);
        $ticket->update(['assigned_to' => $tech->id, 'status' => 'assigned']);

        $this->upload($owner, $ticket, UploadedFile::fake()->createWithContent('log.txt', "dòng 1\ndòng 2"))->assertRedirect();
        $attachment = TicketAttachment::firstOrFail();

        $this->actingAs($tech)->get("/attachments/{$attachment->id}")->assertOk();

        $this->actingAs($owner)->delete("/attachments/{$attachment->id}")->assertRedirect();
        $this->assertDatabaseCount('ticket_attachments', 0);
        Storage::disk('local')->assertMissing("attachments/{$ticket->id}/{$attachment->stored_name}");
    }

    public function test_limit_of_ten_files_per_ticket(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->ticketOf($owner);

        for ($i = 0; $i < 10; $i++) {
            $this->upload($owner, $ticket, UploadedFile::fake()->createWithContent("f{$i}.pdf", '%PDF-1.4 ' . $i))->assertRedirect();
        }

        $this->upload($owner, $ticket, UploadedFile::fake()->createWithContent('f11.pdf', '%PDF-1.4 x'))->assertSessionHasErrors('file');
        $this->assertDatabaseCount('ticket_attachments', 10);
    }
}
