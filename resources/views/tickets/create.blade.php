@extends('layouts.app')

@section('title', 'Tạo phiếu hỗ trợ mới')
@section('page_title', 'Tạo phiếu hỗ trợ')

@section('header_actions')
    <a href="{{ route('tickets.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-4 py-2 rounded-xl text-sm transition-all">
        <i class="fa-solid fa-arrow-left text-xs mr-1"></i> Hủy bỏ
    </a>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200/80 bg-slate-50/50">
            <h2 class="text-base font-bold text-slate-800">Gửi yêu cầu hỗ trợ sự cố IT</h2>
            <p class="text-xs text-slate-500 mt-1">Điền chi tiết sự cố bạn đang gặp phải để đội ngũ IT hỗ trợ nhanh nhất.</p>
        </div>

        <form method="POST" action="{{ route('tickets.store') }}" class="p-6 space-y-5">
            @csrf

            <!-- Tiêu đề -->
            <div>
                <label for="title" class="block text-xs font-semibold text-slate-700 uppercase mb-2">Tiêu đề yêu cầu <span class="text-rose-500">*</span></label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required placeholder="VD: Mất kết nối mạng wifi tầng 3"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>

            <!-- Grid 2 Cột -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Danh mục -->
                <div>
                    <label for="category_id" class="block text-xs font-semibold text-slate-700 uppercase mb-2">Danh mục sự cố <span class="text-rose-500">*</span></label>
                    <select name="category_id" id="category_id" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">-- Chọn danh mục --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Mức độ ưu tiên -->
                <div>
                    <label for="priority" class="block text-xs font-semibold text-slate-700 uppercase mb-2">Mức độ ưu tiên <span class="text-rose-500">*</span></label>
                    <select name="priority" id="priority" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Thấp (Low)</option>
                        <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Bình thường (Medium)</option>
                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>Cao (High)</option>
                        <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Khẩn cấp (Urgent)</option>
                    </select>
                </div>
            </div>

            <!-- Tài sản liên quan -->
            <div>
                <label for="asset_id" class="block text-xs font-semibold text-slate-700 uppercase mb-2">Tài sản liên quan (Nếu có)</label>
                <select name="asset_id" id="asset_id" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">-- Không có --</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" {{ old('asset_id') == $asset->id ? 'selected' : '' }}>
                            {{ $asset->asset_code ?? $asset->asset_tag }} - {{ $asset->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Mô tả -->
            <div>
                <label for="description" class="block text-xs font-semibold text-slate-700 uppercase mb-2">Mô tả chi tiết <span class="text-rose-500">*</span></label>
                <textarea name="description" id="description" rows="5" required placeholder="Mô tả cụ thể biểu hiện sự cố, các bước bị lỗi..."
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">{{ old('description') }}</textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="{{ route('tickets.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium text-sm">Hủy</a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm shadow-sm flex items-center space-x-2">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Gửi phiếu hỗ trợ</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection