<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Repositories\AttachmentRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tải tệp lên an toàn (K6, BM8):
 *  - kiểm tra KIỂU TỆP THẬT bằng chữ ký nhị phân (không tin phần mở rộng hay MIME do client gửi);
 *  - giới hạn dung lượng và số tệp mỗi phiếu;
 *  - đổi tên ngẫu nhiên (UUID), lưu ở đĩa "local" (storage/app/private) NGOÀI thư mục public nên không thực thi và không truy cập trực tiếp;
 *  - tải xuống đi qua điểm cuối kiểm quyền (TicketAttachmentPolicy).
 */
class AttachmentService
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    public const MAX_PER_TICKET = 10;
    private const DISK = 'local';

    /** chữ ký nhị phân => [mime, phần mở rộng lưu trữ] */
    private const SIGNATURES = [
        "\xFF\xD8\xFF" => ['image/jpeg', 'jpg'],
        "\x89PNG\r\n\x1A\n" => ['image/png', 'png'],
        '%PDF-' => ['application/pdf', 'pdf'],
    ];

    public function __construct(
        private readonly AttachmentRepository $attachments,
        private readonly AuditService $audit,
    ) {
    }

    /** @throws ValidationException */
    public function store(Ticket $ticket, User $uploader, UploadedFile $file): TicketAttachment
    {
        if ($this->attachments->countFor($ticket) >= self::MAX_PER_TICKET) {
            throw ValidationException::withMessages(['file' => 'Mỗi phiếu chỉ được đính kèm tối đa ' . self::MAX_PER_TICKET . ' tệp.']);
        }

        $path = (string) $file->getRealPath();

        if ($file->getSize() > self::MAX_BYTES || $file->getSize() === 0) {
            throw ValidationException::withMessages(['file' => 'Dung lượng tệp phải lớn hơn 0 và không quá 5 MB.']);
        }

        [$mime, $extension] = $this->detectType($path);

        $stored = Str::uuid()->toString() . '.' . $extension;

        Storage::disk(self::DISK)->putFileAs("attachments/{$ticket->id}", $file, $stored);

        $attachment = $this->attachments->create([
            'ticket_id' => $ticket->id,
            'user_id' => $uploader->id,
            'original_name' => $this->safeName($file->getClientOriginalName(), $extension),
            'stored_name' => $stored,
            'mime_type' => $mime,
            'size' => (int) $file->getSize(),
            'sha256' => hash_file('sha256', $path),
        ]);

        $this->audit->log('attachment.uploaded', $uploader, $attachment, ['ticket_id' => $ticket->id, 'mime' => $mime]);

        return $attachment;
    }

    public function download(TicketAttachment $attachment, User $actor): StreamedResponse
    {
        $this->audit->log('attachment.downloaded', $actor, $attachment, ['ticket_id' => $attachment->ticket_id]);

        return Storage::disk(self::DISK)->download(
            $this->path($attachment),
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type, 'X-Content-Type-Options' => 'nosniff'],
        );
    }

    public function delete(TicketAttachment $attachment, User $actor): void
    {
        Storage::disk(self::DISK)->delete($this->path($attachment));
        $this->attachments->delete($attachment);
        $this->audit->log('attachment.deleted', $actor, null, ['id' => $attachment->id, 'ticket_id' => $attachment->ticket_id]);
    }

    /**
     * Xác định kiểu thật theo chữ ký; chấp nhận văn bản thuần (.txt) nếu không chứa byte NUL.
     *
     * @return array{0: string, 1: string}
     *
     * @throws ValidationException
     */
    private function detectType(string $path): array
    {
        $head = (string) file_get_contents($path, false, null, 0, 16);

        foreach (self::SIGNATURES as $signature => $type) {
            if (str_starts_with($head, $signature)) {
                return $type;
            }
        }

        $sample = (string) file_get_contents($path, false, null, 0, 4096);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (string) finfo_file($finfo, $path) : '';

        if ($mime === 'text/plain' && ! str_contains($sample, "\0") && mb_check_encoding($sample, 'UTF-8')) {
            return ['text/plain', 'txt'];
        }

        throw ValidationException::withMessages(['file' => 'Chỉ chấp nhận tệp JPG, PNG, PDF hoặc TXT hợp lệ (kiểm tra theo nội dung tệp).']);
    }

    /** Tên hiển thị an toàn: bỏ đường dẫn, ký tự điều khiển, giữ tối đa 100 ký tự và đúng phần mở rộng thật. */
    private function safeName(string $original, string $extension): string
    {
        $base = pathinfo(basename(str_replace('\\', '/', $original)), PATHINFO_FILENAME);
        $base = preg_replace('/[^\p{L}\p{N}\-_. ]+/u', '_', $base) ?? 'tep';
        $base = Str::limit(trim($base, " ._"), 100, '');

        return ($base === '' ? 'tep' : $base) . '.' . $extension;
    }

    private function path(TicketAttachment $attachment): string
    {
        return "attachments/{$attachment->ticket_id}/{$attachment->stored_name}";
    }
}
