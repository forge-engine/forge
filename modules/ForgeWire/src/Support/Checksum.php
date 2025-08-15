<?php

namespace App\Modules\ForgeWire\Support;

use Forge\Core\Config\Config;
use Forge\Core\Session\SessionInterface;

final class Checksum
{
    private const K_SIG = ':sig';
    private const K_FP  = ':fp';

    private string $appKey;

    public function __construct(private Config $config)
    {
        $this->appKey = (string) $config->get('security.app_key', '');
        if ($this->appKey === '') {
            throw new \RuntimeException('App key required for ForgeWire checksum.');
        }
    }

    private function canonicalize(mixed $v): mixed
    {
        if (is_array($v)) {
            $isAssoc = array_keys($v) !== range(0, count($v) - 1);
            if ($isAssoc) {
                ksort($v);
            }
            foreach ($v as $k => $val) {
                $v[$k] = $this->canonicalize($val);
            }
            return $v;
        }
        if (is_object($v)) {
            return $this->canonicalize(get_object_vars($v));
        }
        return $v;
    }

    private function canonicalJson(mixed $v): string
    {
        $v = $this->canonicalize($v);
        return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function compute(SessionInterface $session, string $sessionKey, array $ctx): string
    {
        $state  = (array) $session->get($sessionKey, []);
        $models = (array) $session->get($sessionKey . ':models', []);
        $dtos   = (array) $session->get($sessionKey . ':dtos', []);

        $payload = [
            'class' => (string) ($ctx['class'] ?? ''),
            'path'  => (string) ($ctx['path']  ?? ''),
            'sid'   => (string) $session->getId(),
            'v'     => 1,
            'bags'  => [
                'state'  => $state,
                'models' => $models,
                'dtos'   => $dtos,
            ],
        ];

        $json = $this->canonicalJson($payload);
        return hash_hmac('sha256', $json, $this->appKey);
    }

    public function sign(string $sessionKey, SessionInterface $session, array $ctx): string
    {
        $sig = $this->compute($session, $sessionKey, $ctx);
        $session->set($sessionKey . self::K_FP, [
            'class' => (string) ($ctx['class'] ?? ''),
            'path'  => (string) ($ctx['path']  ?? ''),
        ]);
        $session->set($sessionKey . self::K_SIG, $sig);
        return $sig;
    }

    /**
     * Verify client-provided signature and fingerprint.
     * First request may have null checksum → initialize fp and allow.
     */
    public function verify(?string $provided, string $sessionKey, SessionInterface $session, array $ctx): void
    {
        $fp = (array) $session->get($sessionKey . self::K_FP);

        if (empty($fp)) {
            $session->set($sessionKey . self::K_FP, [
                'class' => (string) ($ctx['class'] ?? ''),
                'path'  => (string) ($ctx['path']  ?? ''),
            ]);
            return;
        }

        $expClass = (string) ($fp['class'] ?? '');
        $expPath  = (string) ($fp['path']  ?? '');
        $curClass = (string) ($ctx['class'] ?? '');
        $curPath  = (string) ($ctx['path']  ?? '');
        if (!hash_equals($expClass, $curClass) || !hash_equals($expPath, $curPath)) {
            throw new \RuntimeException('Fingerprint mismatch.');
        }

        $stored = (string) ($session->get($sessionKey . self::K_SIG) ?? '');
        if ($stored === '') {
            return;
        }

        if ($provided === null || !hash_equals($stored, (string) $provided)) {
            $re = $this->compute($session, $sessionKey, $ctx);
            if (!hash_equals($re, (string) $provided)) {
                throw new \RuntimeException('ForgeWire checksum mismatch.');
            }
        }
    }
}
