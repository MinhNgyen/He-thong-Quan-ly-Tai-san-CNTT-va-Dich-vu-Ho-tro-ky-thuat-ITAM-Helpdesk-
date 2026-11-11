<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_sees_public_landing_page_and_protected_pages_require_login(): void
    {
        $this->get('/')->assertOk()->assertSee('Một nơi cho mọi yêu cầu hỗ trợ CNTT', false);
        $this->get('/tickets')->assertRedirect('/login');
        $this->get('/reports')->assertRedirect('/login');
    }
}
