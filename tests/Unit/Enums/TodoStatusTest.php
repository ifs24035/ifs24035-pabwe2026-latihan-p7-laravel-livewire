<?php

namespace Tests\Unit\Enums;

use App\Features\Todo\Enums\TodoStatus;
use PHPUnit\Framework\TestCase;

class TodoStatusTest extends TestCase
{
    public function test_todo_status_labels(): void
    {
        $this->assertEquals('Pending', TodoStatus::PENDING->label());
        $this->assertEquals('In Progress', TodoStatus::IN_PROGRESS->label());
        $this->assertEquals('Completed', TodoStatus::COMPLETED->label());
    }

    public function test_todo_status_badge_classes(): void
    {
        $this->assertStringContainsString('amber', TodoStatus::PENDING->badgeClass());
        $this->assertStringContainsString('blue', TodoStatus::IN_PROGRESS->badgeClass());
        $this->assertStringContainsString('emerald', TodoStatus::COMPLETED->badgeClass());
    }
}

