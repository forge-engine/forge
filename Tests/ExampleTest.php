<?php

namespace Tests;

use Forge\Modules\ForgeTesting\TestCase;

class ExampleTest extends TestCase
{
    public function setUp(): void
    {

    }

    public function testBasicAssertion(): void
    {
        $this->assertTrue(true);
    }

    public function testMathOperations(): void
    {
        $this->assertEquals(4, 2 + 2);
    }

    public function testIncomplete(): void
    {
        $this->markTestIncomplete("Not implemented yet");
    }
}