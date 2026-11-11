<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CatalogService;
use App\Support\CatalogRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** CRUD danh mục dùng chung (Admin). Dữ liệu vào được kiểm tra lại ở máy chủ theo CatalogService::rules(). */
class CatalogController extends Controller
{
    public function __construct(private readonly CatalogService $catalog)
    {
    }

    public function index(string $type): View
    {
        return view('admin.catalog.index', [
            'type' => $type,
            'def' => CatalogRegistry::get($type),
            'items' => $this->catalog->list($type),
        ]);
    }

    public function create(string $type): View
    {
        return view('admin.catalog.form', [
            'type' => $type,
            'def' => CatalogRegistry::get($type),
            'item' => null,
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $data = $request->validate($this->catalog->rules($type));
        $this->catalog->create($type, $data);

        return redirect()->route('admin.catalog.index', $type)->with('success', 'Đã thêm mục mới.');
    }

    public function edit(string $type, int $id): View
    {
        return view('admin.catalog.form', [
            'type' => $type,
            'def' => CatalogRegistry::get($type),
            'item' => $this->catalog->find($type, $id),
        ]);
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $item = $this->catalog->find($type, $id);
        $data = $request->validate($this->catalog->rules($type, $item));
        $this->catalog->update($type, $item, $data);

        return redirect()->route('admin.catalog.index', $type)->with('success', 'Đã cập nhật.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $item = $this->catalog->find($type, $id);

        if (! $this->catalog->delete($type, $item)) {
            return back()->withErrors(['item' => 'Không thể xóa mục đang được sử dụng. Với danh mục sự cố, hãy tắt kích hoạt thay vì xóa.']);
        }

        return redirect()->route('admin.catalog.index', $type)->with('success', 'Đã xóa.');
    }
}
