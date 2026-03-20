<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DiagnosticsGatingTest extends TestCase
{
    // Default mode (no APP_ENV=dev): debug routes must not be registered.
    public function testDebugRoutesAreNotExposedByDefault(): void
    {
        $cmd = 'php -S localhost:8086 -t public > /dev/null 2>&1 & echo $!';
        $pid = (int) trim((string) shell_exec($cmd));

        // Give the built-in server a moment to start.
        usleep(200_000);

        $output = shell_exec('curl -s -o /dev/null -w "%{http_code}" http://localhost:8086/debug/routes');
        $status = (int) trim((string) $output);

        // Stop the server.
        if ($pid > 0) {
            shell_exec('kill ' . $pid . ' > /dev/null 2>&1');
        }

        $this->assertSame(404, $status);
    }

    // Dev mode: debug routes should be registered.
    public function testDebugRoutesAreExposedInDev(): void
    {
        $cmd = 'APP_ENV=dev php -S localhost:8086 -t public > /dev/null 2>&1 & echo $!';
        $pid = (int) trim((string) shell_exec($cmd));

        usleep(200_000);

        $output = shell_exec('curl -s -o /dev/null -w "%{http_code}" http://localhost:8086/debug/routes');
        $status = (int) trim((string) $output);

        if ($pid > 0) {
            shell_exec('kill ' . $pid . ' > /dev/null 2>&1');
        }

        $this->assertSame(200, $status);
    }

    // Default mode (no APP_ENV=dev): phpinfo must not be exposed.
    public function testPhpInfoIsNotExposedByDefault(): void
    {
        $cmd = 'php -S localhost:8086 -t public > /dev/null 2>&1 & echo $!';
        $pid = (int) trim((string) shell_exec($cmd));

        usleep(200_000);

        $output = shell_exec('curl -s -o /dev/null -w "%{http_code}" http://localhost:8086/debug/phpinfo');
        $status = (int) trim((string) $output);

        if ($pid > 0) {
            shell_exec('kill ' . $pid . ' > /dev/null 2>&1');
        }

        $this->assertSame(404, $status);
    }

    // Dev mode: phpinfo should be exposed.
    public function testPhpInfoIsExposedInDev(): void
    {
        $cmd = 'APP_ENV=dev php -S localhost:8086 -t public > /dev/null 2>&1 & echo $!';
        $pid = (int) trim((string) shell_exec($cmd));

        usleep(200_000);

        $output = shell_exec('curl -s -o /dev/null -w "%{http_code}" http://localhost:8086/debug/phpinfo');
        $status = (int) trim((string) $output);

        if ($pid > 0) {
            shell_exec('kill ' . $pid . ' > /dev/null 2>&1');
        }

        $this->assertSame(200, $status);
    }
}
