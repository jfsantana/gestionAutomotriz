<?php

namespace Tests\Feature;

use Tests\TestCase;

class SectionHeadingTest extends TestCase
{
    public function test_section_icons_keep_the_existing_menu_symbols_without_database_queries(): void
    {
        $this->expectsDatabaseQueryCount(0);
        $icons = [
            'dashboard' => "\u{1F4CA}",
            'customers' => "\u{1F465}",
            'vehicles' => "\u{1F697}",
            'services' => "\u{1F527}",
            'maintenance-orders' => "\u{1F4DD}",
            'reminders' => "\u{1F514}",
            'company-profile' => "\u{1F3A8}",
            'staff' => "\u{1F468}\u{200D}\u{1F527}",
            'companies' => "\u{1F3E2}",
            'company-admins' => "\u{1F464}",
            'account' => "\u{1F464}",
            'subscription-plans' => "\u{1F4BC}",
            'company-subscriptions' => "\u{1F4B3}",
            'policies' => "\u{1F4DC}",
            'personal-maintenance' => "\u{2795}",
        ];

        foreach ($icons as $section => $icon) {
            $view = $this->blade('<x-section-icon :name="$section" class="nav-icon" />', compact('section'));

            $view->assertSee($icon)
                ->assertSee('class="nav-icon"', false)
                ->assertSee('aria-hidden="true"', false);
        }
    }

    public function test_page_heading_preserves_title_markup_and_escapes_customer_text_without_database_queries(): void
    {
        $this->expectsDatabaseQueryCount(0);
        $title = '<script>alert("title")</script>';

        $view = $this->blade(
            '<x-page-heading section="customers" class="h3 mb-0" id="page-title">{{ $title }} <span class="badge">SaaS</span></x-page-heading>',
            compact('title'),
        );

        $view->assertSee('<h1', false)
            ->assertSee('id="page-title"', false)
            ->assertSee("\u{1F465}")
            ->assertSee('aria-hidden="true"', false)
            ->assertSee($title)
            ->assertDontSee($title, false)
            ->assertSee('<span class="badge">SaaS</span>', false);
    }
}
