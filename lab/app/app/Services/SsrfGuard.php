<?php

namespace App\Services;

use App\Support\FixMode;

/**
 * Validates outbound webhook URLs. Blocks private, loopback, and link-local
 * targets (including 169.254.169.254) and enforces an HTTPS host allowlist.
 */
final class SsrfGuard
{
    private const BLOCKED_HOSTS = [
        '169.254.169.254',
        'metadata.google.internal',
        'localhost',
        '127.0.0.1',
        '0.0.0.0',
        '::1',
    ];

    /** @return string|null rejection reason, or null if allowed */
    public function validate(string $url): ?string
    {
        if (! FixMode::enabled()) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return 'invalid_url';
        }

        $scheme = strtolower($parts['scheme']);
        if ($scheme !== 'https') {
            return 'scheme_not_allowlisted';
        }

        $host = strtolower($parts['host']);
        foreach (self::BLOCKED_HOSTS as $blocked) {
            if ($host === $blocked || str_ends_with($host, '.'.$blocked)) {
                return 'blocked_host';
            }
        }

        $ips = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        } else {
            $records = @dns_get_record($host, DNS_A + DNS_AAAA);
            if (is_array($records)) {
                foreach ($records as $r) {
                    if (! empty($r['ip'])) {
                        $ips[] = $r['ip'];
                    }
                    if (! empty($r['ipv6'])) {
                        $ips[] = $r['ipv6'];
                    }
                }
            }
        }

        foreach ($ips as $ip) {
            if ($this->isInternalIp($ip)) {
                return 'internal_ip_blocked';
            }
        }

        $allowed = config('lab.webhook_allow_hosts', []);
        $ok = false;
        foreach ($allowed as $a) {
            $a = strtolower((string) $a);
            if ($host === $a || str_ends_with($host, '.'.$a)) {
                $ok = true;
                break;
            }
        }
        if (! $ok) {
            return 'host_not_allowlisted';
        }

        return null;
    }

    public function isInternalIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $long = ip2long($ip);
            if ($long === false) {
                return true;
            }
            $ranges = [
                ['10.0.0.0', '10.255.255.255'],
                ['172.16.0.0', '172.31.255.255'],
                ['192.168.0.0', '192.168.255.255'],
                ['127.0.0.0', '127.255.255.255'],
                ['169.254.0.0', '169.254.255.255'],
                ['0.0.0.0', '0.255.255.255'],
            ];
            foreach ($ranges as [$start, $end]) {
                if ($long >= ip2long($start) && $long <= ip2long($end)) {
                    return true;
                }
            }

            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $lower = strtolower($ip);

            return str_starts_with($lower, 'fe80:')
                || str_starts_with($lower, 'fc')
                || str_starts_with($lower, 'fd')
                || $lower === '::1';
        }

        return true;
    }
}
