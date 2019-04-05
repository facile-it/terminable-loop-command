<?php

declare(strict_types=1);

namespace Facile\TerminableLoop\Tests\E2E;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class TerminateCommandTest extends TestCase
{
    private const BASH_COMMAND = __DIR__ . '/../../bin/run_terminable_command.sh';
    private const CONSOLE_COMMAND = __DIR__ . '/../Stub/console';
    private const STUB_COMMAND = 'stub:terminable:sleep';

    /**
     * @dataProvider commandLineProvider
     *
     * @param string[] $commandLine
     */
    public function testStubCommand(array $commandLine): void
    {
        $process = new Process($commandLine);
        $process->setTimeout(1);
        $process->enableOutput();
        $process->run();

        $this->assertNotEquals(127, $process->getExitCode(), 'Command not found: ' . $process->getCommandLine());
        $this->assertStringContainsString('Starting ' . self::STUB_COMMAND, $process->getOutput());
        $this->assertStringContainsString('No sleep ', $process->getOutput());
        $this->assertSame(1, $process->getExitCode());
    }

    public function commandLineProvider(): array
    {
        return [
            [
                [
                    self::CONSOLE_COMMAND,
                    self::STUB_COMMAND,
                ],
            ],
            [
                [
                    self::BASH_COMMAND,
                    self::CONSOLE_COMMAND,
                    self::STUB_COMMAND,
                ],
            ],
        ];
    }
}
