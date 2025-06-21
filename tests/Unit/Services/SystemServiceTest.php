<?php

use App\Services\SystemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

uses(RefreshDatabase::class);

describe('SystemService', function () {
    beforeEach(function () {
        $this->systemService = new SystemService;
        Cache::flush();
    });

    it('can get system information', function () {
        $systemInfo = $this->systemService->getSystemInfo();

        expect($systemInfo)->toBeArray();
        expect($systemInfo)->toHaveKey('hostname');
        expect($systemInfo)->toHaveKey('host_ip');
        expect($systemInfo)->toHaveKey('uptime');
        expect($systemInfo)->toHaveKey('load_average');
        expect($systemInfo)->toHaveKey('cpu');
        expect($systemInfo)->toHaveKey('memory');
        expect($systemInfo)->toHaveKey('disk');
        expect($systemInfo)->toHaveKey('os');
        expect($systemInfo)->toHaveKey('kernel');
    });

    it('caches system information', function () {
        // First call
        $systemInfo1 = $this->systemService->getSystemInfo();

        // Second call should use cache
        $systemInfo2 = $this->systemService->getSystemInfo();

        expect($systemInfo1)->toBe($systemInfo2);
        expect(Cache::has('system_info'))->toBeTrue();
    });

    it('can get mail queue status', function () {
        Process::fake([
            'mailq' => Process::result(output: "Mail queue is empty\n\n-- 0 Kbytes in 0 Requests."),
        ]);

        $queueStatus = $this->systemService->getMailQueueStatus();

        expect($queueStatus)->toHaveKey('queue_output');
        expect($queueStatus)->toHaveKey('queue_count');
        expect($queueStatus)->toHaveKey('last_updated');
        expect($queueStatus['queue_count'])->toBe(0);
    });

    it('can parse queue count from mailq output', function () {
        Process::fake([
            'mailq' => Process::result(output: "Mail queue:\n-Queue ID- --Size-- ----Arrival Time---- -Sender/Recipient-------\nABC123     1234     Mon Dec 25 10:00:00  sender@example.com\n                                           recipient@test.com\n\n-- 5 Kbytes in 3 Requests."),
        ]);

        $queueStatus = $this->systemService->getMailQueueStatus();

        expect($queueStatus['queue_count'])->toBe(3);
    });

    it('can refresh mail logs', function () {
        Process::fake([
            'sudo pflogs' => Process::result(),
        ]);

        $result = $this->systemService->refreshMailLogs();

        expect($result['success'])->toBeTrue();
        expect($result['message'])->toContain('refreshed successfully');
    });

    it('handles mail log refresh failure', function () {
        Process::fake([
            'sudo pflogs' => Process::result(exitCode: 1, errorOutput: 'Permission denied'),
        ]);

        $result = $this->systemService->refreshMailLogs();

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('Failed to refresh');
    });

    it('can get process list', function () {
        Process::fake([
            'sudo processes' => Process::result(output: "PID  COMMAND\n1234 nginx\n5678 postfix"),
        ]);

        $processList = $this->systemService->getProcessList();

        expect($processList)->toHaveKey('processes');
        expect($processList)->toHaveKey('last_updated');
        expect($processList['processes'])->toContain('nginx');
        expect($processList['processes'])->toContain('postfix');
    });

    it('caches process list', function () {
        Process::fake([
            'sudo processes' => Process::result(output: 'Process list'),
        ]);

        // First call
        $processList1 = $this->systemService->getProcessList();

        // Second call should use cache
        $processList2 = $this->systemService->getProcessList();

        expect($processList1)->toBe($processList2);
        expect(Cache::has('process_list'))->toBeTrue();
    });

    it('can execute allowed system commands', function () {
        Process::fake([
            'uptime' => Process::result(output: 'up 5 days'),
        ]);

        $result = $this->systemService->executeSystemCommand('uptime');

        expect($result['success'])->toBeTrue();
        expect($result['output'])->toBe('up 5 days');
        expect($result['message'])->toContain('executed successfully');
    });

    it('blocks unauthorized commands', function () {
        $result = $this->systemService->executeSystemCommand('rm -rf /');

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toBe('Command not allowed');
        expect($result['output'])->toBe('');
    });

    it('handles command execution timeout', function () {
        Process::fake([
            'uptime' => Process::result()->throw(new \Exception('Timeout')),
        ]);

        $result = $this->systemService->executeSystemCommand('uptime');

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('error occurred');
    });

    it('can format bytes correctly', function () {
        expect($this->systemService->formatBytes(1024))->toBe('1.00 KB');
        expect($this->systemService->formatBytes(1048576))->toBe('1.00 MB');
        expect($this->systemService->formatBytes(1073741824))->toBe('1.00 GB');
        expect($this->systemService->formatBytes(0))->toBe('0.00 B');
    });

    it('can get process information', function () {
        Process::fake([
            'ps aux --sort=-%cpu | head -20' => Process::result(output: "USER PID %CPU %MEM COMMAND\nroot 1234 10.5 2.3 nginx"),
            'systemctl is-active postfix' => Process::result(output: 'active'),
            'systemctl is-enabled postfix' => Process::result(output: 'enabled'),
            'systemctl is-active nginx' => Process::result(output: 'active'),
            'systemctl is-enabled nginx' => Process::result(output: 'enabled'),
            'ps aux' => Process::result(output: "USER PID STAT COMMAND\nroot 1234 R nginx\nroot 5678 S postfix"),
            'ps -eo pid,ppid,cmd,etime --sort=start_time | tail -10' => Process::result(output: "PID PPID ETIME CMD\n1234 1 00:05 nginx"),
        ]);

        $processInfo = $this->systemService->getProcessInfo();

        expect($processInfo)->toHaveKey('system_processes');
        expect($processInfo)->toHaveKey('critical_services');
        expect($processInfo)->toHaveKey('resource_usage');
        expect($processInfo)->toHaveKey('recent_activity');
    });

    it('handles system process parsing', function () {
        Process::fake([
            'ps aux --sort=-%cpu | head -20' => Process::result(output: "USER       PID %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND\nroot      1234 10.5  2.3 123456  7890 ?        S    10:00   0:01 nginx: master process\nwww-data  5678  5.2  1.8 654321  4567 ?        S    10:01   0:00 nginx: worker process"),
        ]);

        $processInfo = $this->systemService->getProcessInfo();
        $processes = $processInfo['system_processes'];

        expect($processes)->toHaveCount(2);
        expect($processes[0]['user'])->toBe('root');
        expect($processes[0]['pid'])->toBe('1234');
        expect($processes[0]['cpu'])->toBe('10.5');
        expect($processes[0]['memory'])->toBe('2.3');
    });

    it('can check critical service status', function () {
        Process::fake([
            'systemctl is-active postfix' => Process::result(output: 'active'),
            'systemctl is-enabled postfix' => Process::result(output: 'enabled'),
            'systemctl is-active nginx' => Process::result(output: 'inactive'),
            'systemctl is-enabled nginx' => Process::result(output: 'disabled'),
        ]);

        $processInfo = $this->systemService->getProcessInfo();
        $services = $processInfo['critical_services'];

        $postfixService = collect($services)->firstWhere('name', 'postfix');
        $nginxService = collect($services)->firstWhere('name', 'nginx');

        expect($postfixService['active'])->toBeTrue();
        expect($postfixService['enabled'])->toBeTrue();
        expect($postfixService['status'])->toBe('running');

        expect($nginxService['active'])->toBeFalse();
        expect($nginxService['enabled'])->toBeFalse();
        expect($nginxService['status'])->toBe('stopped');
    });

    it('can get process resource usage statistics', function () {
        Process::fake([
            'ps aux' => Process::result(output: "USER PID STAT COMMAND\nroot 1234 R nginx\nroot 5678 S postfix\nroot 9012 Z defunct\nuser 3456 D waiting"),
        ]);

        $processInfo = $this->systemService->getProcessInfo();
        $usage = $processInfo['resource_usage'];

        expect($usage['total'])->toBe(4);
        expect($usage['running'])->toBe(1);
        expect($usage['sleeping'])->toBe(2);
        expect($usage['zombie'])->toBe(1);
    });

    it('handles errors gracefully in process info', function () {
        Process::fake([
            'ps aux --sort=-%cpu | head -20' => Process::result(exitCode: 1),
            'systemctl is-active *' => Process::result(exitCode: 1),
            'ps aux' => Process::result(exitCode: 1),
            'ps -eo pid,ppid,cmd,etime --sort=start_time | tail -10' => Process::result(exitCode: 1),
        ]);

        $processInfo = $this->systemService->getProcessInfo();

        expect($processInfo['system_processes'])->toBe([]);
        expect($processInfo['resource_usage']['total'])->toBe(0);
    });
});
