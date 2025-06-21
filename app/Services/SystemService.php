<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class SystemService
{
    public function getSystemInfo(): array
    {
        return Cache::remember('system_info', 30, function () {
            $info = [
                'hostname' => gethostname(),
                'host_ip' => gethostbyname(gethostname()),
                'uptime' => $this->getUptime(),
                'load_average' => sys_getloadavg(),
                'cpu' => $this->getCpuInfo(),
                'memory' => $this->getMemoryInfo(),
                'disk' => $this->getDiskInfo(),
                'os' => $this->getOsInfo(),
                'kernel' => $this->getKernelVersion(),
            ];

            return $info;
        });
    }

    public function getMailQueueStatus(): array
    {
        try {
            $result = Process::run('mailq');
            $output = $result->output();

            $queueInfo = [
                'queue_output' => $output,
                'queue_count' => $this->parseQueueCount($output),
                'last_updated' => now(),
            ];

            // Get postfix logs if available
            $logFile = '/tmp/pflogsumm.log';
            if (is_readable($logFile)) {
                $queueInfo['pflog_content'] = file_get_contents($logFile);
                $queueInfo['pflog_age_minutes'] = round((time() - filemtime($logFile)) / 60);
            } else {
                $queueInfo['pflog_content'] = 'Log file not available';
                $queueInfo['pflog_age_minutes'] = 0;
            }

            return $queueInfo;

        } catch (\Exception $e) {
            Log::error('Error getting mail queue status: '.$e->getMessage());

            return [
                'queue_output' => 'Error retrieving queue information',
                'queue_count' => 0,
                'pflog_content' => 'Error retrieving log information',
                'pflog_age_minutes' => 0,
                'last_updated' => now(),
            ];
        }
    }

    public function refreshMailLogs(): array
    {
        try {
            $result = Process::run('sudo pflogs');

            if ($result->successful()) {
                return [
                    'success' => true,
                    'message' => 'Mail logs refreshed successfully',
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to refresh mail logs: '.$result->errorOutput(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error refreshing mail logs: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'An error occurred while refreshing mail logs',
            ];
        }
    }

    public function getProcessList(): array
    {
        return Cache::remember('process_list', 60, function () {
            try {
                $result = Process::run('sudo processes');

                return [
                    'processes' => $result->output(),
                    'last_updated' => now(),
                ];

            } catch (\Exception $e) {
                Log::error('Error getting process list: '.$e->getMessage());

                return [
                    'processes' => 'Error retrieving process information',
                    'last_updated' => now(),
                ];
            }
        });
    }

    public function executeSystemCommand(string $command, array $allowedCommands = []): array
    {
        // Default allowed commands for security
        $defaultAllowed = [
            'mailq',
            'sudo pflogs',
            'sudo processes',
            'uptime',
            'free -h',
            'df -h',
        ];

        $allowed = array_merge($defaultAllowed, $allowedCommands);

        if (! in_array($command, $allowed)) {
            return [
                'success' => false,
                'message' => 'Command not allowed',
                'output' => '',
            ];
        }

        try {
            $result = Process::timeout(30)->run($command);

            return [
                'success' => $result->successful(),
                'message' => $result->successful() ? 'Command executed successfully' : 'Command failed',
                'output' => $result->output(),
                'error' => $result->errorOutput(),
                'exit_code' => $result->exitCode(),
            ];

        } catch (\Exception $e) {
            Log::error("Error executing command '{$command}': ".$e->getMessage());

            return [
                'success' => false,
                'message' => 'An error occurred while executing the command',
                'output' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getUptime(): string
    {
        if (is_readable('/proc/uptime')) {
            $uptime = (float) explode(' ', file_get_contents('/proc/uptime'))[0];

            return $this->secondsToTime($uptime);
        }

        return 'Unknown';
    }

    private function getCpuInfo(): array
    {
        $cpuInfo = [
            'name' => 'Unknown CPU',
            'cores' => 0,
            'usage' => 0,
        ];

        if (is_readable('/proc/cpuinfo')) {
            $cpuData = file_get_contents('/proc/cpuinfo');

            // Get CPU name
            if (preg_match('/model name\s*:\s*(.+)/i', $cpuData, $matches)) {
                $cpuInfo['name'] = trim($matches[1]);
            }

            // Count cores
            $cpuInfo['cores'] = substr_count($cpuData, 'processor');
        }

        // Calculate CPU usage
        $cpuInfo['usage'] = $this->getCpuUsage();

        return $cpuInfo;
    }

    private function getCpuUsage(): float
    {
        if (! is_readable('/proc/stat')) {
            return 0;
        }

        $stat1 = file('/proc/stat')[0];
        usleep(100000); // 0.1 second delay
        $stat2 = file('/proc/stat')[0];

        $info1 = preg_split('/\s+/', trim($stat1));
        $info2 = preg_split('/\s+/', trim($stat2));

        $dif = [
            'user' => $info2[1] - $info1[1],
            'nice' => $info2[2] - $info1[2],
            'sys' => $info2[3] - $info1[3],
            'idle' => $info2[4] - $info1[4],
        ];

        $total = array_sum($dif);

        return $total > 0 ? round((($total - $dif['idle']) / $total) * 100, 2) : 0;
    }

    private function getMemoryInfo(): array
    {
        $memInfo = [
            'total' => 0,
            'used' => 0,
            'free' => 0,
            'usage_percent' => 0,
        ];

        if (is_readable('/proc/meminfo')) {
            $memData = file_get_contents('/proc/meminfo');
            $lines = explode("\n", $memData);
            $mem = [];

            foreach ($lines as $line) {
                if (preg_match('/^(\w+):\s*(\d+)\s*kB/', $line, $matches)) {
                    $mem[$matches[1]] = (int) $matches[2] * 1024; // Convert to bytes
                }
            }

            if (isset($mem['MemTotal'])) {
                $memInfo['total'] = $mem['MemTotal'];
                $memInfo['free'] = $mem['MemFree'] + ($mem['Cached'] ?? 0) + ($mem['SReclaimable'] ?? 0) + ($mem['Buffers'] ?? 0);
                $memInfo['used'] = $memInfo['total'] - $memInfo['free'];
                $memInfo['usage_percent'] = $memInfo['total'] > 0 ? round(($memInfo['used'] / $memInfo['total']) * 100, 2) : 0;
            }
        }

        return $memInfo;
    }

    private function getDiskInfo(): array
    {
        $diskInfo = [
            'total' => 0,
            'used' => 0,
            'free' => 0,
            'usage_percent' => 0,
        ];

        $total = disk_total_space('/');
        $free = disk_free_space('/');

        if ($total && $free) {
            $diskInfo['total'] = $total;
            $diskInfo['free'] = $free;
            $diskInfo['used'] = $total - $free;
            $diskInfo['usage_percent'] = round(($diskInfo['used'] / $total) * 100, 2);
        }

        return $diskInfo;
    }

    private function getOsInfo(): string
    {
        if (is_readable('/etc/os-release')) {
            $osData = file_get_contents('/etc/os-release');
            if (preg_match('/PRETTY_NAME="([^"]+)"/', $osData, $matches)) {
                return $matches[1];
            }
        }

        return php_uname('s').' '.php_uname('r');
    }

    private function getKernelVersion(): string
    {
        if (is_readable('/proc/version')) {
            $version = file_get_contents('/proc/version');
            if (preg_match('/Linux version (\S+)/', $version, $matches)) {
                return $matches[1];
            }
        }

        return php_uname('r');
    }

    private function parseQueueCount(string $queueOutput): int
    {
        if (preg_match('/(\d+) Kbytes in (\d+) Requests?/', $queueOutput, $matches)) {
            return (int) $matches[2];
        }

        if (str_contains($queueOutput, 'Mail queue is empty')) {
            return 0;
        }

        // Count individual messages
        return substr_count($queueOutput, "\n") - 2; // Subtract header and footer lines
    }

    private function secondsToTime(float $seconds): string
    {
        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$seconds");
        $diff = $dtF->diff($dtT);

        $parts = [];
        if ($diff->d > 0) {
            $parts[] = $diff->d.' day'.($diff->d != 1 ? 's' : '');
        }
        if ($diff->h > 0) {
            $parts[] = $diff->h.' hour'.($diff->h != 1 ? 's' : '');
        }
        if ($diff->i > 0) {
            $parts[] = $diff->i.' minute'.($diff->i != 1 ? 's' : '');
        }

        return ! empty($parts) ? implode(', ', $parts) : 'Less than a minute';
    }

    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return number_format($bytes / pow(1024, $power), 2).' '.$units[$power];
    }

    public function getProcessInfo(): array
    {
        try {
            $info = [
                'system_processes' => $this->getSystemProcesses(),
                'critical_services' => $this->getCriticalServices(),
                'resource_usage' => $this->getProcessResourceUsage(),
                'recent_activity' => $this->getRecentProcessActivity(),
            ];

            return $info;

        } catch (\Exception $e) {
            Log::error('Error getting process info: '.$e->getMessage());

            return [
                'error' => 'Unable to retrieve process information',
                'system_processes' => [],
                'critical_services' => [],
            ];
        }
    }

    private function getSystemProcesses(): array
    {
        try {
            $result = Process::run('ps aux --sort=-%cpu | head -20');
            if ($result->successful()) {
                $lines = explode("\n", trim($result->output()));
                $processes = [];

                // Skip header line
                for ($i = 1; $i < count($lines); $i++) {
                    if (! empty($lines[$i])) {
                        $parts = preg_split('/\s+/', $lines[$i], 11);
                        if (count($parts) >= 11) {
                            $processes[] = [
                                'user' => $parts[0],
                                'pid' => $parts[1],
                                'cpu' => $parts[2],
                                'memory' => $parts[3],
                                'vsz' => $parts[4],
                                'rss' => $parts[5],
                                'tty' => $parts[6],
                                'stat' => $parts[7],
                                'start' => $parts[8],
                                'time' => $parts[9],
                                'command' => $parts[10],
                            ];
                        }
                    }
                }

                return $processes;
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getCriticalServices(): array
    {
        $services = [
            'postfix',
            'dovecot',
            'nginx',
            'apache2',
            'mysql',
            'mariadb',
            'postgresql',
            'redis',
            'memcached',
        ];

        $serviceStatus = [];

        foreach ($services as $service) {
            try {
                $result = Process::run("systemctl is-active {$service}");
                $active = $result->successful() && trim($result->output()) === 'active';

                $enabledResult = Process::run("systemctl is-enabled {$service}");
                $enabled = $enabledResult->successful() && trim($enabledResult->output()) === 'enabled';

                $serviceStatus[] = [
                    'name' => $service,
                    'active' => $active,
                    'enabled' => $enabled,
                    'status' => $active ? 'running' : 'stopped',
                ];
            } catch (\Exception $e) {
                $serviceStatus[] = [
                    'name' => $service,
                    'active' => false,
                    'enabled' => false,
                    'status' => 'unknown',
                ];
            }
        }

        return $serviceStatus;
    }

    private function getProcessResourceUsage(): array
    {
        try {
            $totalProcesses = 0;
            $runningProcesses = 0;
            $sleepingProcesses = 0;
            $zombieProcesses = 0;

            $result = Process::run('ps aux');
            if ($result->successful()) {
                $lines = explode("\n", trim($result->output()));
                $totalProcesses = count($lines) - 1; // Subtract header

                foreach ($lines as $line) {
                    if (preg_match('/\s+([RSDZTW])\s+/', $line, $matches)) {
                        switch ($matches[1]) {
                            case 'R':
                                $runningProcesses++;
                                break;
                            case 'S':
                            case 'D':
                                $sleepingProcesses++;
                                break;
                            case 'Z':
                                $zombieProcesses++;
                                break;
                        }
                    }
                }
            }

            return [
                'total' => $totalProcesses,
                'running' => $runningProcesses,
                'sleeping' => $sleepingProcesses,
                'zombie' => $zombieProcesses,
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'running' => 0,
                'sleeping' => 0,
                'zombie' => 0,
            ];
        }
    }

    private function getRecentProcessActivity(): array
    {
        try {
            $result = Process::run('ps -eo pid,ppid,cmd,etime --sort=start_time | tail -10');
            if ($result->successful()) {
                $lines = explode("\n", trim($result->output()));
                $recentProcesses = [];

                foreach ($lines as $line) {
                    if (! empty($line) && ! str_contains($line, 'PID')) {
                        $parts = preg_split('/\s+/', trim($line), 4);
                        if (count($parts) >= 4) {
                            $recentProcesses[] = [
                                'pid' => $parts[0],
                                'ppid' => $parts[1],
                                'etime' => $parts[3],
                                'command' => $parts[2] ?? 'Unknown',
                            ];
                        }
                    }
                }

                return $recentProcesses;
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
