<?php

declare(strict_types=1);

namespace Webuzo;

use Webuzo\WebuzoService;

class NetDataService
{
    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private static function config(): array
    {
        $webuzo = config('webuzo');

        return [
            'host'    => rtrim((string) $webuzo['host'], '/'),
            'port'    => $webuzo['netdata']['port'],
            'scheme'  => $webuzo['netdata']['scheme'],
            'timeout' => $webuzo['netdata']['timeout'],
        ];
    }

    private static function get(string $path, array $query = []): array
    {
        $cfg = self::config();
        $url = $cfg['scheme'] . '://' . $cfg['host'] . ':' . $cfg['port'] . $path;

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $cfg['timeout'],
            CURLOPT_CONNECTTIMEOUT => $cfg['timeout'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);

        $raw   = curl_exec($ch);
        $error = curl_error($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => $error, 'data' => null];
        }

        if ($code !== 200) {
            return ['success' => false, 'message' => "HTTP $code", 'data' => null];
        }

        $data = json_decode($raw, true);

        return ['success' => true, 'data' => $data];
    }

    // -------------------------------------------------------------------------
    // Server Info
    // -------------------------------------------------------------------------

    /**
     * معلومات عامة عن الـ NetData agent (إصدار, OS, hostname, إلخ)
     */
    public static function getInfo(): array
    {
        $res = self::get('/api/v1/info');

        if (!$res['success']) {
            return $res;
        }

        $d = $res['data'];

        return [
            'success'  => true,
            'hostname' => $d['hostname'] ?? 'N/A',
            'version'  => $d['version'] ?? 'N/A',
            'os'       => $d['os_name'] ?? ($d['os'] ?? 'N/A'),
            'timezone' => $d['timezone'] ?? 'N/A',
            'uptime'   => $d['uptime'] ?? null,
            'raw'      => $d,
        ];
    }

    // -------------------------------------------------------------------------
    // Charts list
    // -------------------------------------------------------------------------

    /**
     * قائمة بجميع الـ charts المتاحة على هذا السيرفر
     */
    public static function getCharts(): array
    {
        $res = self::get('/api/v1/charts');

        if (!$res['success']) {
            return $res;
        }

        return [
            'success' => true,
            'total'   => count($res['data']['charts'] ?? []),
            'charts'  => array_keys($res['data']['charts'] ?? []),
            'raw'     => $res['data']['charts'] ?? [],
        ];
    }

    // -------------------------------------------------------------------------
    // CPU
    // -------------------------------------------------------------------------

    /**
     * استخدام الـ CPU (system, user, idle, iowait...)
     */
    public static function getCpu(int $lastSeconds = 60): array
    {
        $res = self::get('/api/v1/data', [
            'chart'   => 'system.cpu',
            'after'   => -$lastSeconds,
            'points'  => 1,
            'group'   => 'average',
            'format'  => 'json',
            'options' => 'jsonwrap',
        ]);

        if (!$res['success']) {
            return $res;
        }

        $latest = array_combine(
            $res['data']['dimension_names'] ?? [],
            $res['data']['latest_values'] ?? []
        );

        $idle  = $latest['idle'] ?? 100;
        $used  = round(100 - $idle, 2);

        return [
            'success'     => true,
            'used_percent'=> $used,
            'idle_percent'=> round((float) $idle, 2),
            'dimensions'  => $latest,
        ];
    }

    // -------------------------------------------------------------------------
    // RAM
    // -------------------------------------------------------------------------

    /**
     * استخدام الـ RAM (used, free, cached, buffers)
     */
    public static function getRam(int $lastSeconds = 60): array
    {
        $res = self::get('/api/v1/data', [
            'chart'   => 'system.ram',
            'after'   => -$lastSeconds,
            'points'  => 1,
            'group'   => 'average',
            'format'  => 'json',
            'options' => 'jsonwrap',
        ]);

        if (!$res['success']) {
            return $res;
        }

        $dims = array_combine(
            $res['data']['dimension_names'] ?? [],
            $res['data']['latest_values'] ?? []
        );

        $used  = ($dims['used'] ?? 0) + ($dims['buffers'] ?? 0) + ($dims['active'] ?? 0);
        $free  = $dims['free'] ?? 0;
        $total = array_sum(array_values($dims));
        $pct   = $total > 0 ? round($used / $total * 100, 2) : 0;

        return [
            'success'      => true,
            'used_mb'      => round($used / 1024 / 1024, 2),
            'free_mb'      => round($free / 1024 / 1024, 2),
            'total_mb'     => round($total / 1024 / 1024, 2),
            'used_percent' => $pct,
            'dimensions'   => $dims,
        ];
    }

    // -------------------------------------------------------------------------
    // Disk
    // -------------------------------------------------------------------------

    /**
     * استخدام الـ Disk I/O
     */
    public static function getDiskIo(int $lastSeconds = 60): array
    {
        $res = self::get('/api/v1/data', [
            'chart'   => 'system.io',
            'after'   => -$lastSeconds,
            'points'  => 1,
            'group'   => 'average',
            'format'  => 'json',
            'options' => 'jsonwrap',
        ]);

        if (!$res['success']) {
            return $res;
        }

        $dims = array_combine(
            $res['data']['dimension_names'] ?? [],
            $res['data']['latest_values'] ?? []
        );

        return [
            'success'     => true,
            'read_mb_s'   => round(abs($dims['in'] ?? 0) / 1024, 4),
            'write_mb_s'  => round(abs($dims['out'] ?? 0) / 1024, 4),
            'dimensions'  => $dims,
        ];
    }

    /**
     * مساحة الـ Disk لكل partition
     */
    public static function getDiskSpace(): array
    {
        $chartsRes = self::get('/api/v1/charts');

        if (!$chartsRes['success']) {
            return $chartsRes;
        }

        $diskCharts = array_filter(
            array_keys($chartsRes['data']['charts'] ?? []),
            fn($k) => str_starts_with($k, 'disk_space.')
        );

        $partitions = [];
        foreach ($diskCharts as $chart) {
            $res = self::get('/api/v1/data', [
                'chart'   => $chart,
                'after'   => -60,
                'points'  => 1,
                'group'   => 'average',
                'format'  => 'json',
                'options' => 'jsonwrap',
            ]);

            if (!$res['success']) {
                continue;
            }

            $dims = array_combine(
                $res['data']['dimension_names'] ?? [],
                $res['data']['latest_values'] ?? []
            );

            $used  = $dims['used'] ?? 0;
            $avail = $dims['avail'] ?? 0;
            $total = $used + $avail;
            $pct   = $total > 0 ? round($used / $total * 100, 2) : 0;

            $partitions[] = [
                'partition'    => str_replace('disk_space.', '', $chart),
                'used_gb'      => round($used / 1024, 2),
                'free_gb'      => round($avail / 1024, 2),
                'total_gb'     => round($total / 1024, 2),
                'used_percent' => $pct,
            ];
        }

        return ['success' => true, 'partitions' => $partitions];
    }

    // -------------------------------------------------------------------------
    // Network
    // -------------------------------------------------------------------------

    /**
     * استخدام الشبكة (received/sent)
     */
    public static function getNetwork(int $lastSeconds = 60): array
    {
        $res = self::get('/api/v1/data', [
            'chart'   => 'system.net',
            'after'   => -$lastSeconds,
            'points'  => 1,
            'group'   => 'average',
            'format'  => 'json',
            'options' => 'jsonwrap',
        ]);

        if (!$res['success']) {
            return $res;
        }

        $dims = array_combine(
            $res['data']['dimension_names'] ?? [],
            $res['data']['latest_values'] ?? []
        );

        return [
            'success'         => true,
            'received_mb_s'   => round(abs($dims['received'] ?? 0) / 1024, 4),
            'sent_mb_s'       => round(abs($dims['sent'] ?? 0) / 1024, 4),
            'dimensions'      => $dims,
        ];
    }

    // -------------------------------------------------------------------------
    // Load Average
    // -------------------------------------------------------------------------

    /**
     * Load average (1m, 5m, 15m)
     */
    public static function getLoadAverage(): array
    {
        $res = self::get('/api/v1/data', [
            'chart'   => 'system.load',
            'after'   => -60,
            'points'  => 1,
            'group'   => 'average',
            'format'  => 'json',
            'options' => 'jsonwrap',
        ]);

        if (!$res['success']) {
            return $res;
        }

        $dims = array_combine(
            $res['data']['dimension_names'] ?? [],
            $res['data']['latest_values'] ?? []
        );

        return [
            'success' => true,
            'load_1'  => round($dims['load1'] ?? $dims['1'] ?? 0, 3),
            'load_5'  => round($dims['load5'] ?? $dims['5'] ?? 0, 3),
            'load_15' => round($dims['load15'] ?? $dims['15'] ?? 0, 3),
        ];
    }

    // -------------------------------------------------------------------------
    // Alerts / Alarms
    // -------------------------------------------------------------------------

    /**
     * جلب جميع التنبيهات النشطة
     */
    public static function getAlerts(bool $allAlerts = false): array
    {
        $res = self::get('/api/v1/alarms', $allAlerts ? ['all' => 'true'] : []);

        if (!$res['success']) {
            return $res;
        }

        $alarms  = $res['data']['alarms'] ?? [];
        $summary = ['critical' => 0, 'warning' => 0, 'clear' => 0, 'undefined' => 0];
        $list    = [];

        foreach ($alarms as $name => $alarm) {
            $status = strtolower($alarm['status'] ?? 'undefined');
            $summary[$status] = ($summary[$status] ?? 0) + 1;

            $list[] = [
                'name'       => $name,
                'chart'      => $alarm['chart'] ?? 'N/A',
                'status'     => $status,
                'value'      => $alarm['value'] ?? null,
                'units'      => $alarm['units'] ?? '',
                'info'       => $alarm['info'] ?? '',
                'last_change'=> $alarm['last_status_change'] ?? null,
            ];
        }

        return [
            'success' => true,
            'total'   => count($list),
            'summary' => $summary,
            'alarms'  => $list,
        ];
    }

    // -------------------------------------------------------------------------
    // Metric for specific chart
    // -------------------------------------------------------------------------

    /**
     * جلب بيانات أي chart بالاسم
     */
    public static function getChartData(string $chart, int $lastSeconds = 300, int $points = 60): array
    {
        $res = self::get('/api/v1/data', [
            'chart'   => $chart,
            'after'   => -$lastSeconds,
            'points'  => $points,
            'group'   => 'average',
            'format'  => 'json',
            'options' => 'jsonwrap',
        ]);

        if (!$res['success']) {
            return $res;
        }

        return [
            'success'    => true,
            'chart'      => $chart,
            'dimensions' => $res['data']['dimension_names'] ?? [],
            'data'       => $res['data'],
        ];
    }

    // -------------------------------------------------------------------------
    // User resource usage (Webuzo quota + NetData CPU/RAM)
    // -------------------------------------------------------------------------

    /**
     * استهلاك مستخدم معين:
     *  - Disk + Bandwidth + Email + DB  : من Webuzo Admin API (quota)
     *  - CPU + RAM                      : من NetData (users.cpu / users.mem charts)
     */
    public static function getUserUsage(string $username): array
    {
        // --- Webuzo quota data ---
        $userResult = WebuzoService::getUser($username);
        $quota      = [];

        // --- Webuzo resource limits (CPU/RAM plan) ---
        // mem_max is returned as a string with suffix: "128M", "2G", "512K", etc.
        $parseMemToMb = static function (?string $val): ?float {
            if ($val === null || $val === '' || $val === '0') {
                return null;
            }
            $num    = (float) $val;
            $suffix = strtoupper(substr(trim($val), -1));
            return match ($suffix) {
                'G'     => round($num * 1024, 2),
                'M'     => round($num, 2),
                'K'     => round($num / 1024, 2),
                default => round($num, 2),
            };
        };

        $limitsResult = WebuzoService::getUserResourceLimits($username);
        $cpuLimit     = $limitsResult['success'] ? $limitsResult['cpu_quota']  : null;
        $memLimit     = $limitsResult['success'] ? $parseMemToMb($limitsResult['memory_max']) : null;
        $taskLimit    = $limitsResult['success'] ? $limitsResult['max_tasks']  : null;
        $resourcePlan = $limitsResult['success'] ? $limitsResult['plan']       : null;

        if ($userResult['success']) {
            $resources = $userResult['user']['resources'];
            $disk      = $resources['disk'] ?? [];
            $bw        = $resources['bandwidth'] ?? [];

            // bytes → MB (1 MB = 1,048,576 bytes)
            $diskUsed  = isset($disk['used_bytes'])  ? (float) $disk['used_bytes']  / 1048576 : 0.0;
            $diskLimit = isset($disk['limit_bytes'])
                ? (float) $disk['limit_bytes'] / 1048576
                : (isset($disk['hard_limit']) ? (float) $disk['hard_limit'] * 1024 : 0.0);
            $diskPct   = (float) ($disk['percent'] ?? ($diskLimit > 0 ? $diskUsed / $diskLimit * 100 : 0));

            $bwUsed  = isset($bw['used_bytes'])  ? (float) $bw['used_bytes']  / 1048576 : 0.0;
            $bwLimit = isset($bw['limit_bytes']) ? (float) $bw['limit_bytes'] / 1048576 : 0.0;
            $bwPct   = (float) ($bw['percent'] ?? ($bwLimit > 0 ? $bwUsed / $bwLimit * 100 : 0));

            $quota = [
                'disk' => [
                    'used_mb'   => round($diskUsed, 3),
                    'limit_mb'  => round($diskLimit, 3),
                    'used_gb'   => round($diskUsed / 1024, 3),
                    'limit_gb'  => round($diskLimit / 1024, 3),
                    'percent'   => $diskPct,
                    'raw'       => $disk,
                ],
                'bandwidth' => [
                    'used_mb'   => round($bwUsed, 3),
                    'limit_mb'  => round($bwLimit, 3),
                    'used_gb'   => round($bwUsed / 1024, 3),
                    'limit_gb'  => round($bwLimit / 1024, 3),
                    'percent'   => $bwPct,
                    'raw'       => $bw,
                ],
                'email_accounts' => $resources['email_accounts'] ?? [],
                'databases'      => $resources['databases'] ?? [],
            ];
        }

        // --- NetData CPU per user ---
        // Chart ID format: user.{username}_cpu_utilization
        $cpuUsage = null;
        $cpuRes = self::get('/api/v1/data', [
            'chart'   => "user.{$username}_cpu_utilization",
            'after'   => -60,
            'points'  => 1,
            'group'   => 'average',
            'format'  => 'json',
            'options' => 'jsonwrap',
        ]);
        if ($cpuRes['success'] && !empty($cpuRes['data']['latest_values'])) {
            $total = 0;
            foreach ($cpuRes['data']['latest_values'] as $val) {
                if (is_numeric($val)) {
                    $total += abs((float) $val);
                }
            }
            $cpuUsage = round($total, 3);
        }

        // --- NetData RAM per user ---
        // Chart ID format: user.{username}_mem_usage (in MiB)
        $memUsage = null;
        $memRes = self::get('/api/v1/data', [
            'chart'   => "user.{$username}_mem_usage",
            'after'   => -60,
            'points'  => 1,
            'group'   => 'average',
            'format'  => 'json',
            'options' => 'jsonwrap',
        ]);
        if ($memRes['success'] && !empty($memRes['data']['latest_values'])) {
            $total = 0;
            foreach ($memRes['data']['latest_values'] as $val) {
                if (is_numeric($val)) {
                    $total += abs((float) $val);
                }
            }
            $memUsage = round($total, 2);
        }

        // --- NetData processes per user ---
        // Chart ID format: user.{username}_processes
        $processCount = null;
        $procRes = self::get('/api/v1/data', [
            'chart'   => "user.{$username}_processes",
            'after'   => -60,
            'points'  => 1,
            'group'   => 'average',
            'format'  => 'json',
            'options' => 'jsonwrap',
        ]);
        if ($procRes['success'] && !empty($procRes['data']['latest_values'])) {
            $total = 0;
            foreach ($procRes['data']['latest_values'] as $val) {
                if (is_numeric($val)) {
                    $total += abs((float) $val);
                }
            }
            $processCount = (int) round($total);
        }

        $cpuLimitVal = $cpuLimit !== null ? (float) $cpuLimit : null;
        $memLimitVal = $memLimit !== null ? (float) $memLimit : null;

        return [
            'success'       => true,
            'username'      => $username,
            'resource_plan' => $resourcePlan,
            'cpu' => [
                'used_percent'    => $cpuUsage,
                'limit_percent'   => $cpuLimitVal,
                'plan_percent'    => ($cpuUsage !== null && $cpuLimitVal > 0)
                                        ? round($cpuUsage / $cpuLimitVal * 100, 1)
                                        : null,
                'note'            => $cpuUsage === null ? 'apps.plugin not tracking this user (user may be idle or plugin disabled)' : null,
            ],
            'ram' => [
                'used_mb'      => $memUsage,
                'limit_mb'     => $memLimitVal,
                'plan_percent' => ($memUsage !== null && $memLimitVal > 0)
                                        ? round($memUsage / $memLimitVal * 100, 1)
                                        : null,
                'note'         => $memUsage === null ? 'apps.plugin not tracking this user (user may be idle or plugin disabled)' : null,
            ],
            'processes' => [
                'count'        => $processCount,
                'max_tasks'    => $taskLimit !== null ? (int) $taskLimit : null,
                'plan_percent' => ($processCount !== null && $taskLimit > 0)
                                        ? round($processCount / (int) $taskLimit * 100, 1)
                                        : null,
            ],
            'quota' => $quota ?: null,
        ];
    }

    // -------------------------------------------------------------------------
    // Full server snapshot
    // -------------------------------------------------------------------------

    /**
     * لقطة شاملة للسيرفر: CPU + RAM + Disk + Network + Load + Alerts
     */
    public static function getServerSnapshot(): array
    {
        return [
            'success'    => true,
            'info'       => self::getInfo(),
            'cpu'        => self::getCpu(),
            'ram'        => self::getRam(),
            'disk_io'    => self::getDiskIo(),
            'disk_space' => self::getDiskSpace(),
            'network'    => self::getNetwork(),
            'load'       => self::getLoadAverage(),
            'alerts'     => self::getAlerts(),
        ];
    }
}
