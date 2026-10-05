<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Support\Facades\DB;
use TDSoft\AiTutor\Events\WritingAssessmentCompleted;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class EventTest extends FoundationTestCase
{
    public function test_assessment_events_dispatch_only_after_successful_commit(): void
    {
        $manager = new DatabaseTransactionsManager;
        DB::connection()->setTransactionManager($manager);
        $this->app['events']->setTransactionManagerResolver(fn () => $manager);
        $received = [];
        $this->app['events']->listen(WritingAssessmentCompleted::class, function ($event) use (&$received) {
            $received[] = $event->eventId;
        });
        $event = new WritingAssessmentCompleted('event-1', 'submission-1', 'learner-1', 'lesson-1', [], 'rubric-v1');
        DB::beginTransaction();
        $this->app['events']->dispatch($event);
        $this->assertSame([], $received);
        DB::rollBack();
        $this->assertSame([], $received);
        DB::beginTransaction();
        $this->app['events']->dispatch($event);
        $this->assertSame([], $received);
        DB::commit();
        $this->assertSame(['event-1'], $received);
    }
}
