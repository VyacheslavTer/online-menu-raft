<?php

declare(strict_types=1);

final class Auth
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_WINDOW_SECONDS = 900;

    public function __construct(private PDO $pdo)
    {
    }

    public function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id, email, name FROM users WHERE id = :id AND is_active = 1');
        $stmt->execute(['id' => (int) $_SESSION['user_id']]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function attempt(string $email, string $password): bool
    {
        $email = trim($email);
        $ip = $this->clientIp();
        $this->clearOldLoginAttempts();

        if ($this->isLoginBlocked($email, $ip)) {
            throw new RuntimeException('Слишком много попыток входа. Попробуйте через ' . $this->blockedMinutesLeft($email, $ip) . ' мин.');
        }

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            $this->recordFailedLogin($email, $ip);
            return false;
        }

        $this->clearLoginAttempts($email, $ip);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];

        return true;
    }

    private function clientIp(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        return substr($ip, 0, 64);
    }

    private function cutoffTime(): string
    {
        return date('Y-m-d H:i:s', time() - self::LOGIN_WINDOW_SECONDS);
    }

    private function clearOldLoginAttempts(): void
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < :cutoff');
            $stmt->execute(['cutoff' => $this->cutoffTime()]);
        } catch (Throwable) {
            return;
        }
    }

    private function isLoginBlocked(string $email, string $ip): bool
    {
        return $this->failedLoginCount($email, $ip) >= self::MAX_LOGIN_ATTEMPTS;
    }

    private function failedLoginCount(string $email, string $ip): int
    {
        try {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE email = :email AND ip_address = :ip AND attempted_at >= :cutoff');
            $stmt->execute([
                'email' => $email,
                'ip' => $ip,
                'cutoff' => $this->cutoffTime(),
            ]);

            return (int) $stmt->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    private function blockedMinutesLeft(string $email, string $ip): int
    {
        try {
            $stmt = $this->pdo->prepare('SELECT MIN(attempted_at) FROM login_attempts WHERE email = :email AND ip_address = :ip AND attempted_at >= :cutoff');
            $stmt->execute([
                'email' => $email,
                'ip' => $ip,
                'cutoff' => $this->cutoffTime(),
            ]);
            $firstAttempt = (string) $stmt->fetchColumn();
            $remaining = self::LOGIN_WINDOW_SECONDS - max(0, time() - strtotime($firstAttempt));

            return max(1, (int) ceil($remaining / 60));
        } catch (Throwable) {
            return (int) ceil(self::LOGIN_WINDOW_SECONDS / 60);
        }
    }

    private function recordFailedLogin(string $email, string $ip): void
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)');
            $stmt->execute(['email' => $email, 'ip' => $ip]);
        } catch (Throwable) {
            return;
        }
    }

    private function clearLoginAttempts(string $email, string $ip): void
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM login_attempts WHERE email = :email AND ip_address = :ip');
            $stmt->execute(['email' => $email, 'ip' => $ip]);
        } catch (Throwable) {
            return;
        }
    }

    public function logout(): void
    {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
    }

    public function requireLogin(): void
    {
        if (!$this->check()) {
            redirect('/manage-raft/');
        }
    }

    public function usingDefaultPassword(): bool
    {
        return (string) config('admin.password') === 'change-me-now';
    }
}