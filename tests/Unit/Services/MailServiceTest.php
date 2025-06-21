<?php

use App\Models\Vhost;
use App\Models\Vmail;
use App\Services\MailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;

uses(RefreshDatabase::class);

describe('MailService', function () {
    beforeEach(function () {
        $this->mailService = new MailService;
    });

    it('can create a mailbox with valid data', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        $result = $this->mailService->createMailbox('test@example.com', 'password123');

        expect($result['success'])->toBeTrue();
        expect($result['message'])->toContain('created successfully');
        expect(Vmail::where('user', 'test@example.com')->exists())->toBeTrue();
    });

    it('validates email format when creating mailbox', function () {
        $result = $this->mailService->createMailbox('invalid-email', 'password123');

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('Invalid email format');
    });

    it('checks domain exists when creating mailbox', function () {
        $result = $this->mailService->createMailbox('test@nonexistent.com', 'password123');

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('does not exist');
    });

    it('prevents creating duplicate mailboxes', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);
        Vmail::factory()->create(['user' => 'test@example.com']);

        $result = $this->mailService->createMailbox('test@example.com', 'password123');

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('already exists');
    });

    it('generates password when not provided', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        $result = $this->mailService->createMailbox('test@example.com');

        expect($result['success'])->toBeTrue();
        expect($result['password'])->toBeString();
        expect(strlen($result['password']))->toBeGreaterThanOrEqual(12);
    });

    it('can delete an existing mailbox', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com']);
        $vmail = Vmail::factory()->create(['user' => 'test@example.com']);

        $result = $this->mailService->deleteMailbox('test@example.com');

        expect($result['success'])->toBeTrue();
        expect($result['message'])->toContain('deleted successfully');
        expect(Vmail::where('user', 'test@example.com')->exists())->toBeFalse();
    });

    it('handles deleting non-existent mailbox', function () {
        $result = $this->mailService->deleteMailbox('nonexistent@example.com');

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('does not exist');
    });

    it('can change mailbox password', function () {
        $vmail = Vmail::factory()->create(['user' => 'test@example.com']);

        $result = $this->mailService->changePassword('test@example.com', 'newpassword123');

        expect($result['success'])->toBeTrue();
        expect($result['message'])->toContain('Password changed successfully');

        $vmail->refresh();
        expect(password_verify('newpassword123', $vmail->password))->toBeTrue();
    });

    it('validates password length when changing password', function () {
        $vmail = Vmail::factory()->create(['user' => 'test@example.com']);

        $result = $this->mailService->changePassword('test@example.com', 'short');

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('at least 8 characters');
    });

    it('can get mailbox statistics', function () {
        $vmail = Vmail::factory()->create(['user' => 'test@example.com']);

        $result = $this->mailService->getMailboxStats('test@example.com');

        expect($result['success'])->toBeTrue();
        expect($result['data'])->toHaveKey('user');
        expect($result['data'])->toHaveKey('home');
        expect($result['data'])->toHaveKey('active');
        expect($result['data']['user'])->toBe('test@example.com');
    });

    it('validates email domain against existing vhosts', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com', 'status' => true]);

        $valid = $this->mailService->validateEmailDomain('test@example.com');
        $invalid = $this->mailService->validateEmailDomain('test@nonexistent.com');

        expect($valid)->toBeTrue();
        expect($invalid)->toBeFalse();
    });

    it('rejects emails from inactive domains', function () {
        $vhost = Vhost::factory()->create(['domain' => 'example.com', 'status' => false]);

        $result = $this->mailService->validateEmailDomain('test@example.com');

        expect($result)->toBeFalse();
    });

    it('can generate secure passwords', function () {
        $password1 = $this->mailService->generatePassword();
        $password2 = $this->mailService->generatePassword();

        expect($password1)->toBeString();
        expect($password2)->toBeString();
        expect($password1)->not->toBe($password2);
        expect(strlen($password1))->toBe(12);
        expect(strlen($password2))->toBe(12);
    });

    it('can generate passwords of custom length', function () {
        $shortPassword = $this->mailService->generatePassword(8);
        $longPassword = $this->mailService->generatePassword(16);

        expect(strlen($shortPassword))->toBe(8);
        expect(strlen($longPassword))->toBe(16);
    });

    it('can get mail information', function () {
        Vhost::factory()->count(3)->create(['status' => true]);
        Vhost::factory()->create(['status' => false]);
        Vmail::factory()->count(5)->create(['active' => true]);
        Vmail::factory()->create(['active' => false]);

        $mailInfo = $this->mailService->getMailInfo();

        expect($mailInfo)->toHaveKey('statistics');
        expect($mailInfo['statistics']['total_domains'])->toBe(4);
        expect($mailInfo['statistics']['active_domains'])->toBe(3);
        expect($mailInfo['statistics']['total_mailboxes'])->toBe(6);
        expect($mailInfo['statistics']['active_mailboxes'])->toBe(5);
    });

    it('handles system command failures gracefully', function () {
        Process::fake([
            'sudo addvmail *' => Process::result(exitCode: 1, errorOutput: 'Command failed'),
        ]);

        $vhost = Vhost::factory()->create(['domain' => 'example.com']);

        $result = $this->mailService->createMailbox('test@example.com', 'password123');

        expect($result['success'])->toBeFalse();
        expect($result['message'])->toContain('Failed to create mailbox system files');

        // Ensure database rollback occurred
        expect(Vmail::where('user', 'test@example.com')->exists())->toBeFalse();
    });

    it('can check postfix status', function () {
        Process::fake([
            'systemctl is-active postfix' => Process::result(output: 'active'),
        ]);

        $mailInfo = $this->mailService->getMailInfo();

        expect($mailInfo['mail_system']['status']['running'])->toBeTrue();
        expect($mailInfo['mail_system']['status']['status'])->toBe('running');
    });

    it('can get mail queue size', function () {
        Process::fake([
            'mailq | tail -1' => Process::result(output: '5 Kbytes in 3 Requests'),
        ]);

        $mailInfo = $this->mailService->getMailInfo();

        expect($mailInfo['mail_system']['queue_size'])->toBe(3);
    });

    it('handles empty mail queue', function () {
        Process::fake([
            'mailq | tail -1' => Process::result(output: 'Mail queue is empty'),
        ]);

        $mailInfo = $this->mailService->getMailInfo();

        expect($mailInfo['mail_system']['queue_size'])->toBe(0);
    });

    it('can get postfix version', function () {
        Process::fake([
            'postconf mail_version' => Process::result(output: 'mail_version = 3.6.4'),
        ]);

        $mailInfo = $this->mailService->getMailInfo();

        expect($mailInfo['mail_system']['version'])->toBe('3.6.4');
    });

    it('can get recent mail logs', function () {
        Process::fake([
            'tail -20 /var/log/mail.log' => Process::result(output: "Line 1\nLine 2\nLine 3"),
        ]);

        $mailInfo = $this->mailService->getMailInfo();

        expect($mailInfo['logs'])->toBeArray();
        expect($mailInfo['logs'])->toHaveCount(3);
        expect($mailInfo['logs'][0])->toBe('Line 1');
    });

    it('handles mail log errors gracefully', function () {
        Process::fake([
            'tail -20 /var/log/mail.log' => Process::result(exitCode: 1),
        ]);

        $mailInfo = $this->mailService->getMailInfo();

        expect($mailInfo['logs'])->toBeArray();
        expect($mailInfo['logs'][0])->toContain('Error retrieving mail logs');
    });
});
