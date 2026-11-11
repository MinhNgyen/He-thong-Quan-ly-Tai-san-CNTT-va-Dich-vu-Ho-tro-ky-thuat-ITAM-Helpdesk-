<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * REST API phiếu hỗ trợ. Dùng lại TicketService/TicketPolicy như giao diện web nên
 * phạm vi dữ liệu và phân quyền giống hệt: Admin thấy tất cả, Technician thấy phiếu của mình
 * và phiếu chưa ai nhận, Employee chỉ thấy phiếu do mình tạo.
 */
class TicketController extends Controller
{
    public function __construct(private readonly TicketService $tickets)
    {
    }

    /** Dữ liệu tham chiếu để tạo phiếu: danh mục, mức ưu tiên, tài sản có thể gắn. */
    public function options(): JsonResponse
    {
        $options = $this->tickets->createFormOptions();

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $options['categories']->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
                'priorities' => array_map(
                    fn (TicketPriority $p) => ['value' => $p->value, 'label' => $p->label()],
                    TicketPriority::cases(),
                ),
                'assets' => $options['assets']->map(fn ($a) => [
                    'id' => $a->id, 'asset_code' => $a->asset_code, 'name' => $a->name,
                ])->values(),
            ],
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $tickets = $this->tickets->list($request->user(), [
            'status' => $request->string('status')->toString(),
            'q' => $request->string('q')->toString(),
        ]);

        return TicketResource::collection($tickets)->additional([
            'success' => true,
            'message' => 'Lấy danh sách phiếu thành công.',
        ]);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->tickets->create($request->user(), $request->validated());

        return $this->detail($ticket, 'Tạo phiếu hỗ trợ thành công.')->response()->setStatusCode(201);
    }

    public function show(Ticket $ticket): TicketResource
    {
        Gate::authorize('view', $ticket);

        return $this->detail($ticket, 'Lấy chi tiết phiếu thành công.');
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        $ticket = $this->tickets->update($request->user(), $ticket, $request->validated(), $request->canAssign());

        return $this->detail($ticket, 'Cập nhật phiếu thành công.');
    }

    public function comment(StoreCommentRequest $request, Ticket $ticket): JsonResponse
    {
        $this->tickets->addComment($request->user(), $ticket, $request->validated('content'));

        return $this->detail($ticket, 'Đã thêm bình luận.')->response()->setStatusCode(201);
    }

    /** Technician tự nhận phiếu chưa có người xử lý. */
    public function claim(Request $request, Ticket $ticket): JsonResponse|TicketResource
    {
        Gate::authorize('claim', $ticket);

        if (! $this->tickets->claim($request->user(), $ticket)) {
            return response()->json([
                'success' => false,
                'message' => 'Phiếu này đã có người nhận xử lý.',
            ], 409);
        }

        return $this->detail($ticket, 'Bạn đã nhận xử lý phiếu này.');
    }

    private function detail(Ticket $ticket, string $message): TicketResource
    {
        return (new TicketResource($this->tickets->loadDetail($ticket->refresh())))
            ->additional(['success' => true, 'message' => $message]);
    }
}
