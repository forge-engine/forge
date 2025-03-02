<?php

namespace Forge\Http;

class Session
{
    private bool $started = false;

    /**
     * Start the session.
     *
     * @param array<string,mixed> $options
     * @throws \Exception
     */
    public function start(array $options = []): void
    {
        if ($this->started) {
            return;
        }

        ini_set('session.cookie_httponly', true);
        ini_set('session.cookie_secure', true);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', true);
        ini_set('session.use_only_cookies', true);

        $defaults = [
        ];

        $sessionOptions = array_merge($defaults, $options);

        $attempt = 0;
        $maxAttempts = 3;

        while ($attempt < $maxAttempts) {
            if (@session_start($sessionOptions)) {
                $this->started = true;
                return;
            }
            $attempt++;
        }

        throw new \Exception("Failed to start session after {$maxAttempts} attempts.");
    }

    /**
     * Get a session value.
     *
     * @param string $key The session key.
     * @param mixed $default The default value to return if the key is not found.
     * @return mixed
     */
    public function get(string $key, $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value.
     *
     * @param string $key The session key.
     * @param mixed $value The value to set.
     * @return void
     */
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Regenerate the session ID.
     *
     * @param bool $deleteOldSession Whether to delete the old session file (default: true).
     * @return void
     */
    public function regenerate(bool $deleteOldSession = true): void
    {
        session_regenerate_id($deleteOldSession);
    }

    /**
     * Destroy the session (logout).
     *
     * @return void
     */
    public function destroy(): void
    {
        if ($this->started) {
            session_unset();
            session_destroy();
            $this->started = false;
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', 0, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
            }
        }
    }

    /**
     * Check if session has been started.
     *
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->started;
    }
}