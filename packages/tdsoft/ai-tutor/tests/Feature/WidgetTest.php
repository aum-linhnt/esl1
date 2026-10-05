<?php

namespace TDSoft\AiTutor\Tests\Feature;

use TDSoft\AiTutor\Contracts\Entitlements;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Tests\FoundationTestCase;
use TDSoft\AiTutor\Widget\Widget;
use TDSoft\AiTutor\Widget\WidgetSettings;

final class WidgetTest extends FoundationTestCase
{
    public function test_widget_visibility_requires_license_and_matching_lesson(): void
    {
        $this->assertTrue((new Widget('course-1', 'lesson-1'))->shouldRender());
        $this->assertFalse((new Widget('other-course', 'lesson-1'))->shouldRender());
        $this->lms->allowed = false;
        $this->assertFalse((new Widget('course-1', 'lesson-1'))->shouldRender());
        $this->lms->allowed = true;
        $this->app->instance(Entitlements::class, new class implements Entitlements
        {
            public function allows(string $module): bool
            {
                return false;
            }
        });
        $this->assertFalse((new Widget('course-1', 'lesson-1'))->shouldRender());
        $this->assertSame(0, $this->provider->calls);
    }

    public function test_widget_css_configuration_is_bounded_and_allowlisted(): void
    {
        $this->assertSame(420, WidgetSettings::resolve([])['width']);
        foreach ([
            ['desktop_panel_width' => '420; background:url(evil)'],
            ['desktop_panel_width' => 10000],
            ['launcher_position' => 'arbitrary'],
            ['lesson_chat_mode' => 'modal'],
        ] as $config) {
            try {
                WidgetSettings::resolve($config);
                $this->fail('Unsafe config accepted');
            } catch (AiException $e) {
                $this->assertSame('AI_WIDGET_CONFIG_INVALID', $e->errorCode);
            }
        }
    }
}
