<?php

return [
    /*
     * Chính sách bảo mật nội dung (CSP) - BM2, BM12.
     * Giao diện hiện nạp Tailwind (cdn.tailwindcss.com) và Font Awesome/Google Fonts từ CDN nên phải cho phép các nguồn này
     * ('unsafe-inline' và 'unsafe-eval' là điều kiện để script Tailwind CDN chạy). Đây là hạn chế đã nêu ở báo cáo:
     * khi chuyển sang biên dịch Tailwind bằng Vite có thể siết lại còn script-src 'self'.
     */
    'csp' => env('SECURITY_CSP', implode('; ', [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com",
        "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com",
        "font-src 'self' data: https://cdnjs.cloudflare.com https://fonts.gstatic.com",
        "img-src 'self' data:",
        "connect-src 'self'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'none'",
    ])),

    // HSTS chỉ gửi khi yêu cầu đến qua HTTPS.
    'hsts' => 'max-age=31536000; includeSubDomains',
];
