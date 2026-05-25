<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected int $initialObLevel = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initialObLevel = ob_get_level();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > $this->initialObLevel) {
            ob_end_clean();
        }

        parent::tearDown();
    }
}
