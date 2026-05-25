<?php

namespace Tests\Unit;

use App\Http\Controllers\QueueBoardController;
use PHPUnit\Framework\TestCase;

class QueueBoardPriorityLaneSuffixTest extends TestCase
{
    public function test_suffix_is_added_when_priority_id_is_present(): void
    {
        $text = 'Queue number A0001, please proceed to counter number 1.';
        $this->assertSame(
            'Queue number A0001, please proceed to counter number 1. Priority lane.',
            QueueBoardController::applyPriorityLaneSuffix($text, 5),
        );
    }

    public function test_suffix_is_not_added_when_priority_id_is_null_or_empty(): void
    {
        $text = 'Queue number A0001, please proceed to counter number 1.';

        $this->assertSame($text, QueueBoardController::applyPriorityLaneSuffix($text, null));
        $this->assertSame($text, QueueBoardController::applyPriorityLaneSuffix($text, ''));
        $this->assertSame($text, QueueBoardController::applyPriorityLaneSuffix($text, '   '));
    }

    public function test_suffix_is_added_only_once(): void
    {
        $text = 'Queue number A0001, please proceed to counter number 1. Priority lane.';
        $this->assertSame($text, QueueBoardController::applyPriorityLaneSuffix($text, 1));
        $this->assertSame($text, QueueBoardController::applyPriorityLaneSuffix($text, '1'));
    }
}

