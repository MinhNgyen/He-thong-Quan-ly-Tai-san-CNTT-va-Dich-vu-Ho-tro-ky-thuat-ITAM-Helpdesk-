@php
    // Bảng màu "brand" theo vai trò; các view chỉ dùng class brand-* nên đổi vai trò là đổi màu toàn giao diện.
    $palettes = [
        'indigo' => [50 => '238 242 255', 100 => '224 231 255', 200 => '199 210 254', 300 => '165 180 252', 400 => '129 140 248', 500 => '99 102 241', 600 => '79 70 229', 700 => '67 56 202', 800 => '55 48 163', 900 => '49 46 129'],
        'teal' => [50 => '240 253 250', 100 => '204 251 241', 200 => '153 246 228', 300 => '94 234 212', 400 => '45 212 191', 500 => '20 184 166', 600 => '13 148 136', 700 => '15 118 110', 800 => '17 94 89', 900 => '19 78 74'],
        'sky' => [50 => '240 249 255', 100 => '224 242 254', 200 => '186 230 253', 300 => '125 211 252', 400 => '56 189 248', 500 => '14 165 233', 600 => '2 132 199', 700 => '3 105 161', 800 => '7 89 133', 900 => '12 74 110'],
    ];
    $palette = $palettes[$paletteName ?? 'indigo'];
@endphp
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="@yield('meta_description', 'ITAM & Helpdesk: hệ thống quản lý tài sản CNTT và dịch vụ hỗ trợ kỹ thuật, có cam kết SLA theo giờ làm việc.')">
<meta name="theme-color" content="#4f46e5">
<link rel="icon" href="{{ asset('favicon.ico') }}">
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: { sans: ['Inter', 'sans-serif'] },
                colors: {
                    brand: {
                        @foreach (array_keys($palette) as $shade)
                        {{ $shade }}: 'rgb(var(--brand-{{ $shade }}) / <alpha-value>)',
                        @endforeach
                    }
                }
            }
        }
    }
</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root { @foreach ($palette as $shade => $rgb) --brand-{{ $shade }}: {{ $rgb }}; @endforeach }
    body { font-family: 'Inter', sans-serif; }
    /* Khả năng truy cập (WCAG 2.1 AA): chỉ báo tiêu điểm rõ ràng khi dùng bàn phím và nâng độ tương phản chữ phụ trên nền sáng. */
    :focus-visible { outline: 3px solid #1d4ed8; outline-offset: 2px; }
    main .text-slate-400, main .text-slate-300 { color: #64748b; }
    @media (prefers-reduced-motion: reduce) { * { transition: none !important; animation: none !important; } }
</style>
