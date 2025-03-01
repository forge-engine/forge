<?php

namespace Forge\Core\Helpers;

use Exception;
use InvalidArgumentException;
use Random\RandomException;

class UUID
{
    /**
     * Generate an ID string of the specified type.
     *
     * Supported types: 'uuid' (version 1 or 4), 'nanoid', 'ulid'.
     * Default type is 'uuid' version 4.
     *
     * @param string $type The type of ID to generate ('uuid', 'nanoid', 'ulid'). Default is 'uuid'.
     * @param array $options Type-specific options (e.g., ['version' => 1] for UUID, ['size' => 21] for Nano ID).
     * @return string An ID string of the specified type.
     * @throws InvalidArgumentException for invalid type or options.
     * @throws Exception if random_bytes() or time-based generation fails.
     */
    public static function generate(string $type = 'uuid', array $options = []): string
    {
        $type = strtolower($type);
        switch ($type) {
            case 'uuid':
                $version = $options['version'] ?? 4;
                return self::generateUuid($version);
            case 'nanoid':
                $size = $options['size'] ?? 21;
                return self::generateNanoId($size);
            case 'ulid':
                return self::generateUlid();
            default:
                throw new InvalidArgumentException("Unsupported ID type: {$type}. Supported types are 'uuid', 'nanoid', 'ulid'.");
        }
    }

    /**
     * Generate a UUID string of the specified version (1 or 4).
     *
     * @param int $version UUID version to generate (1 or 4).
     * @return string A UUID string.
     * @throws InvalidArgumentException if an unsupported version is requested.
     * @throws Exception if random_bytes() or time-based UUID generation fails.
     */
    private static function generateUuid(int $version): string
    {
        switch ($version) {
            case 1:
                return self::generateUuidVersion1();
            case 4:
                return self::generateUuidVersion4();
            default:
                throw new InvalidArgumentException("Unsupported UUID version: {$version}. Supported versions are 1 and 4.");
        }
    }

    /**
     * Generate a UUID version 4 string (random UUID).
     *
     * @return string A UUID version 4 string.
     * @throws Exception If random_bytes() fails.
     */
    private static function generateUuidVersion4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Generate a UUID version 1 string (time-based UUID).
     *
     * @return string A UUID version 1 string.
     * @throws Exception if microtime() or random_bytes() fails.
     */
    private static function generateUuidVersion1(): string
    {
        $time_low = bin2hex(pack("N", (int)floor(microtime(true) * 100000000) & 0xffffffff));
        $time_mid = bin2hex(pack("v", (int)((floor(microtime(true) * 100000000) >> 32) & 0xffff)));
        $time_hi_and_version = bin2hex(pack("v", (int)(((floor(microtime(true) * 100000000) >> 48) & 0x0fff) | 0x1000))); // Version 1

        $clock_seq_hi_and_reserved = bin2hex(pack("C", random_int(0, 255) & 0x3f | 0x80)); // Variant RFC4122
        $clock_seq_low = bin2hex(pack("C", random_int(0, 255)));

        // Random node ID (replace with MAC address or persistent node ID for true uniqueness in distributed systems)
        $node = bin2hex(random_bytes(6));

        return sprintf('%08s-%04s-%04s-%04s-%012s',
            $time_low, $time_mid, $time_hi_and_version, $clock_seq_hi_and_reserved . $clock_seq_low, $node);
    }

    /**
     * Generate a Nano ID string.
     *
     * @param int $size The desired length of the Nano ID string (default: 21).
     * @return string A Nano ID string.
     * @throws Exception if random_bytes() fails.
     */
    private static function generateNanoId(int $size = 21): string
    {
        $alphabet = 'ModuleSymbhasOwnPr-0123456789ABCDEFGHNRVfgctiUvz_KqYTJkLxpZmW-';
        $mask = (1 << (int)floor(log(strlen($alphabet) - 1, 2))) - 1;
        $bytes = (int)ceil(1.6 * $size); // Good enough approximation for number of bytes needed

        $id = '';
        while (strlen($id) < $size) {
            $randomBytes = random_bytes($bytes);
            for ($i = 0; $i < $bytes; $i++) {
                $byte = ord($randomBytes[$i]) & $mask;
                if (isset($alphabet[$byte])) {
                    $id .= $alphabet[$byte];
                    if (strlen($id) === $size) {
                        break 2; // Break out of both loops once ID is long enough
                    }
                }
            }
        }
        return substr($id, 0, $size); // Ensure correct length if loop overshoots slightly
    }

    /**
     * Generate a ULID string.
     *
     * @return string A ULID string.
     * @throws Exception if microtime() or random_bytes() fails.
     */
    private static function generateUlid(): string
    {
        $timestampMs = (int)floor(microtime(true) * 1000); // Milliseconds timestamp
        $timestampBytes = pack('J*', $timestampMs); // 8 bytes, unsigned long long (network byte order, big-endian)

        $randomBytes = random_bytes(10); // 10 bytes of randomness

        $combinedBytes = substr($timestampBytes, 2, 6) . $randomBytes; // Take last 6 bytes of timestamp (to fit in 10 bytes after base32 encoding) + 10 random bytes

        $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ'; // Crockford Base32 (removed I, L, U for better readability)
        $base32Encoded = '';
        for ($i = 0; $i < 16; $i += 5) { // Process in 5-byte chunks (40 bits)
            $chunk40Bits = 0;
            for ($j = 0; $j < 5 && $i + $j < 16; $j++) {
                $chunk40Bits = ($chunk40Bits << 8) | ord($combinedBytes[$i + $j]);
            }
            for ($j = 7; $j >= 0; $j--) { // Convert 40 bits to 8 base32 characters (5 bits each)
                $index = ($chunk40Bits >> ($j * 5)) & 0x1F; // Get 5 bits
                $base32Encoded .= $alphabet[$index];
            }
        }

        return substr($base32Encoded, 0, 26); // ULID is 26 characters
    }
}