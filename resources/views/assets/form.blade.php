@extends('layouts.app')

@section('title', $editing ? 'Cập nhật tài sản' : 'Thêm tài sản mới')
@section('page_title', $editing ? 'Cập nhật tài sản' : 'Thêm tài sản mới')

@section('header_actions')
    <a href="{{ route('assets.index') }}"
       class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-4 py-2 rounded-xl text-sm transition-all">
        <i class="fa-solid fa-arrow-left text-xs mr-1"></i>
        Quay lại
    </a>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200/80 bg-slate-50/50">
            <h2 class="text-base font-bold text-slate-800">
                {{ $editing ? 'Cập nhật thông tin tài sản' : 'Tạo mới hồ sơ tài sản' }}
            </h2>
        </div>

        <form method="POST"
              action="{{ $editing ? route('assets.update', $asset) : route('assets.store') }}"
              class="p-6 space-y-6">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="asset_code" class="block text-xs font-semibold text-slate-700 uppercase mb-2">
                        Mã tài sản <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="asset_code" id="asset_code"
                           value="{{ old('asset_code', $asset->asset_code) }}" required maxlength="50"
                           placeholder="VD: RACK001"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    @error('asset_code')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-700 uppercase mb-2">
                        Tên tài sản <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" id="name"
                           value="{{ old('name', $asset->name) }}" required maxlength="150"
                           placeholder="VD: Rack A"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    @error('name')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category_id" class="block text-xs font-semibold text-slate-700 uppercase mb-2">
                        Danh mục tài sản <span class="text-rose-500">*</span>
                    </label>
                    <select name="category_id" id="category_id" required
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">— Chọn danh mục —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}"
                                {{ (string) old('category_id', $asset->category_id) === (string) $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="serial_number" class="block text-xs font-semibold text-slate-700 uppercase mb-2">Số Serial</label>
                    <input type="text" name="serial_number" id="serial_number"
                           value="{{ old('serial_number', $asset->serial_number) }}" maxlength="100"
                           placeholder="VD: SN-RACK-001"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    @error('serial_number')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="brand" class="block text-xs font-semibold text-slate-700 uppercase mb-2">Hãng sản xuất</label>
                    <input type="text" name="brand" id="brand"
                           value="{{ old('brand', $asset->brand) }}" maxlength="100"
                           placeholder="VD: Dell"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    @error('brand')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="model" class="block text-xs font-semibold text-slate-700 uppercase mb-2">Model</label>
                    <input type="text" name="model" id="model"
                           value="{{ old('model', $asset->model) }}" maxlength="100"
                           placeholder="VD: PowerEdge R740"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    @error('model')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="purchase_date" class="block text-xs font-semibold text-slate-700 uppercase mb-2">Ngày mua</label>
                    <input type="date" name="purchase_date" id="purchase_date"
                           value="{{ old('purchase_date', $asset->purchase_date?->format('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    @error('purchase_date')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="purchase_price" class="block text-xs font-semibold text-slate-700 uppercase mb-2">Giá mua</label>
                    <input type="number" name="purchase_price" id="purchase_price"
                           value="{{ old('purchase_price', $asset->purchase_price) }}" min="0" step="0.01"
                           placeholder="VD: 25000000"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    @error('purchase_price')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-xs font-semibold text-slate-700 uppercase mb-2">
                        Trạng thái <span class="text-rose-500">*</span>
                    </label>
                    <select name="status" id="status" required
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        @foreach (\App\Enums\AssetStatus::cases() as $status)
                            <option value="{{ $status->value }}"
                                {{ old('status', $asset->status ?: \App\Enums\AssetStatus::Available->value) === $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="parent_id" class="block text-xs font-semibold text-slate-700 uppercase mb-2">Thuộc tài sản (cha)</label>
                    <select name="parent_id" id="parent_id"
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">— Không (tài sản gốc) —</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}"
                                {{ (string) old('parent_id', $asset->parent_id) === (string) $parent->id ? 'selected' : '' }}>
                                {{ $parent->asset_code }} - {{ $parent->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="description" class="block text-xs font-semibold text-slate-700 uppercase mb-2">Mô tả</label>
                <textarea name="description" id="description" rows="4" maxlength="5000"
                          placeholder="Nhập mô tả tài sản..."
                          class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">{{ old('description', $asset->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="{{ route('assets.index') }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium text-sm">Hủy</a>
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm shadow-sm flex items-center space-x-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>{{ $editing ? 'Lưu thay đổi' : 'Thêm mới' }}</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
