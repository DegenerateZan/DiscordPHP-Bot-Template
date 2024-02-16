<?php

namespace Tests;

namespace Tests;

use Commands\Message\Test;
use PHPUnit\Framework\TestCase;

class DynamicCommandTest extends TestCase
{
    public function testCheckExpired()
    {
        // Create a instance of a Command that has a Dynamic Type
        $test = new Test();
        // Set the time expired of the command
        $test->setTimeLimit(time() + 5);
        //$ping->addTimeLimit(5);
        $loop = \React\EventLoop\Factory::create();

        $testCase = $this;

        $loop->addTimer(6, function () use ($test, $testCase) {

            $testCase->assertEquals(true, $test->isExpired());
        });

        $loop->run();

    }
}
