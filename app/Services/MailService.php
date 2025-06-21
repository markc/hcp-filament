<?php

namespace App\Services;

use App\Models\Vhost;
use App\Models\Vmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class MailService
{
    public function createMailbox(string $user, ?string $password = null): array
    {
        try {
            // Validate email format
            if (! filter_var($user, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }

            // Check if domain exists
            $domain = substr(strrchr($user, '@'), 1);
            if (! Vhost::where('domain', $domain)->exists()) {
                return ['success' => false, 'message' => "Domain {$domain} does not exist"];
            }

            // Check if mailbox already exists
            if (Vmail::where('user', $user)->exists()) {
                return ['success' => false, 'message' => "Mailbox {$user} already exists"];
            }

            // Generate password if not provided
            if (! $password) {
                $password = $this->generatePassword();
            }

            // Create mailbox record
            $vmail = Vmail::create([
                'user' => $user,
                'password' => $password,
                'gid' => 1000,
                'uid' => 1000,
                'active' => true,
                'home' => '/home/u/'.$domain.'/'.substr($user, 0, strrpos($user, '@')),
            ]);

            // Execute system command
            $result = Process::run("sudo addvmail {$user}");

            if ($result->failed()) {
                // Rollback database changes
                $vmail->delete();
                Log::error("Failed to create mailbox system files for {$user}: ".$result->errorOutput());

                return ['success' => false, 'message' => 'Failed to create mailbox system files'];
            }

            Log::info("Created mailbox for {$user}");

            return ['success' => true, 'message' => "Mailbox {$user} created successfully", 'password' => $password];

        } catch (\Exception $e) {
            Log::error("Error creating mailbox {$user}: ".$e->getMessage());

            return ['success' => false, 'message' => 'An error occurred while creating the mailbox'];
        }
    }

    public function deleteMailbox(string $user): array
    {
        try {
            $vmail = Vmail::where('user', $user)->first();

            if (! $vmail) {
                return ['success' => false, 'message' => "Mailbox {$user} does not exist"];
            }

            // Execute system command first
            $result = Process::run("sudo delvmail {$user}");

            if ($result->failed()) {
                Log::error("Failed to delete mailbox system files for {$user}: ".$result->errorOutput());

                return ['success' => false, 'message' => 'Failed to delete mailbox system files'];
            }

            // Delete database record
            $vmail->delete();

            Log::info("Deleted mailbox for {$user}");

            return ['success' => true, 'message' => "Mailbox {$user} deleted successfully"];

        } catch (\Exception $e) {
            Log::error("Error deleting mailbox {$user}: ".$e->getMessage());

            return ['success' => false, 'message' => 'An error occurred while deleting the mailbox'];
        }
    }

    public function changePassword(string $user, string $password): array
    {
        try {
            $vmail = Vmail::where('user', $user)->first();

            if (! $vmail) {
                return ['success' => false, 'message' => "Mailbox {$user} does not exist"];
            }

            // Validate password
            if (strlen($password) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
            }

            // Update database
            $vmail->update(['password' => $password]);

            // Execute system command
            $result = Process::run("sudo chpw {$user} '{$password}'");

            if ($result->failed()) {
                Log::error("Failed to change password for {$user}: ".$result->errorOutput());

                return ['success' => false, 'message' => 'Failed to update system password'];
            }

            Log::info("Changed password for {$user}");

            return ['success' => true, 'message' => "Password changed successfully for {$user}"];

        } catch (\Exception $e) {
            Log::error("Error changing password for {$user}: ".$e->getMessage());

            return ['success' => false, 'message' => 'An error occurred while changing the password'];
        }
    }

    public function getMailboxStats(string $user): array
    {
        try {
            $vmail = Vmail::where('user', $user)->first();

            if (! $vmail) {
                return ['success' => false, 'message' => "Mailbox {$user} does not exist"];
            }

            $stats = [
                'user' => $user,
                'home' => $vmail->home,
                'active' => $vmail->active,
                'created_at' => $vmail->created_at,
                'updated_at' => $vmail->updated_at,
            ];

            // Get system stats if available
            $maildir = $vmail->home.'/Maildir';
            if (is_dir($maildir)) {
                $size = $this->getDirectorySize($maildir);
                $stats['size_bytes'] = $size;
                $stats['size_formatted'] = $this->formatBytes($size);

                // Count messages
                $stats['message_count'] = $this->countMessages($maildir);
            }

            return ['success' => true, 'data' => $stats];

        } catch (\Exception $e) {
            Log::error("Error getting mailbox stats for {$user}: ".$e->getMessage());

            return ['success' => false, 'message' => 'An error occurred while getting mailbox stats'];
        }
    }

    public function validateEmailDomain(string $email): bool
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $domain = substr(strrchr($email, '@'), 1);

        return Vhost::where('domain', $domain)->where('status', true)->exists();
    }

    public function generatePassword(int $length = 12): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $password;
    }

    private function getDirectorySize(string $directory): int
    {
        $size = 0;

        if (! is_dir($directory)) {
            return 0;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    private function countMessages(string $maildir): int
    {
        $count = 0;
        $dirs = ['new', 'cur'];

        foreach ($dirs as $dir) {
            $path = $maildir.'/'.$dir;
            if (is_dir($path)) {
                $files = glob($path.'/*');
                $count += count($files);
            }
        }

        return $count;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return number_format($bytes / pow(1024, $power), 2).' '.$units[$power];
    }

    public function getMailInfo(): array
    {
        try {
            $info = [
                'mail_system' => [
                    'status' => $this->checkPostfixStatus(),
                    'version' => $this->getPostfixVersion(),
                    'queue_size' => $this->getMailQueueSize(),
                ],
                'statistics' => [
                    'total_domains' => Vhost::count(),
                    'active_domains' => Vhost::where('status', true)->count(),
                    'total_mailboxes' => Vmail::count(),
                    'active_mailboxes' => Vmail::where('active', true)->count(),
                ],
                'recent_activity' => [
                    'new_mailboxes_today' => Vmail::whereDate('created_at', today())->count(),
                    'new_domains_today' => Vhost::whereDate('created_at', today())->count(),
                ],
                'disk_usage' => $this->getMailDiskUsage(),
                'logs' => $this->getRecentMailLogs(),
            ];

            return $info;

        } catch (\Exception $e) {
            Log::error('Error getting mail info: '.$e->getMessage());

            return [
                'error' => 'Unable to retrieve mail information',
                'statistics' => [
                    'total_domains' => Vhost::count(),
                    'total_mailboxes' => Vmail::count(),
                ],
            ];
        }
    }

    private function checkPostfixStatus(): array
    {
        try {
            $result = Process::run('systemctl is-active postfix');
            $active = $result->successful() && trim($result->output()) === 'active';

            return [
                'running' => $active,
                'status' => $active ? 'running' : 'stopped',
            ];
        } catch (\Exception $e) {
            return ['running' => false, 'status' => 'unknown', 'error' => $e->getMessage()];
        }
    }

    private function getPostfixVersion(): string
    {
        try {
            $result = Process::run('postconf mail_version');
            if ($result->successful()) {
                $output = trim($result->output());

                return str_replace('mail_version = ', '', $output);
            }

            return 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function getMailQueueSize(): int
    {
        try {
            $result = Process::run('mailq | tail -1');
            if ($result->successful()) {
                $output = trim($result->output());
                if (preg_match('/(\d+) Requests/', $output, $matches)) {
                    return (int) $matches[1];
                }
                if (strpos($output, 'Mail queue is empty') !== false) {
                    return 0;
                }
            }

            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getMailDiskUsage(): array
    {
        try {
            $mailDir = '/home/u';
            if (! is_dir($mailDir)) {
                return ['total' => 0, 'used' => 0, 'formatted' => 'N/A'];
            }

            $result = Process::run("du -sb {$mailDir}");
            if ($result->successful()) {
                $size = (int) explode("\t", trim($result->output()))[0];

                return [
                    'used' => $size,
                    'formatted' => $this->formatBytes($size),
                ];
            }

            return ['used' => 0, 'formatted' => '0 B'];
        } catch (\Exception $e) {
            return ['used' => 0, 'formatted' => 'Unknown'];
        }
    }

    private function getRecentMailLogs(): array
    {
        try {
            $result = Process::run('tail -20 /var/log/mail.log');
            if ($result->successful()) {
                $lines = explode("\n", trim($result->output()));

                return array_filter($lines);
            }

            return [];
        } catch (\Exception $e) {
            return ['Error retrieving mail logs: '.$e->getMessage()];
        }
    }
}
