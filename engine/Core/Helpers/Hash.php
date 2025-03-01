<?php

namespace Forge\Core\Helpers;

class Hash
{
    /**
     * Hashes a password using PASSWORD_DEFAULT algorithm.
     *
     * @param string $password The password to hash.
     * @return string|false The hashed password, or false on failure.
     */
    public static function make(string $password): string|false
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verifies if a plain text password matches a hash.
     *
     * @param string $password The plain text password to verify.
     * @param string $hash The hash to compare against.
     * @return bool True if the password matches the hash, false otherwise.
     */
    public static function check(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}