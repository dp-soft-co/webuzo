<?php

declare(strict_types=1);

namespace Webuzo;

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
    // User bandwidth / disk usage from Webuzo
    // -------------------------------------------------------------------------

    /**
     * استهلاك مستخدم معين من الباندويدث والـ Disk من الـ Webuzo API
     * (NetData لا يعرف مستخدمي Webuzo، لذا نجلب هذه البيانات من Webuzo مباشرة)
     */
    public static function getUserUsage(string $username): array
    {
        try {
            $response = \Webuzo\Facades\Webuzo::enduserAs($username)->bandwidth([]);

            if (!$response->ok()) {
                return ['success' => false, 'message' => $response->error()];
            }

            $data = $response->data;

            return [
                'success'   => true,
                'username'  => $username,
                'bandwidth' => [
                    'used_mb'   => $data['used'] ?? 0,
                    'limit_mb'  => $data['limit'] ?? 0,
                    'percent'   => isset($data['used'], $data['limit']) && $data['limit'] > 0
                                    ? round($data['used'] / $data['limit'] * 100, 2)
                                    : 0,
                ],
                'disk' => [
                    'used_mb'   => $data['disk_used'] ?? 0,
                    'limit_mb'  => $data['disk_limit'] ?? 0,
                    'percent'   => isset($data['disk_used'], $data['disk_limit']) && $data['disk_limit'] > 0
                                    ? round($data['disk_used'] / $data['disk_limit'] * 100, 2)
                                    : 0,
                ],
                'raw' => $data,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
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
