<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AmazonAccount;
use App\Models\FbaShipment;
use App\Models\Tenant;
use App\Models\User;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'users' => User::count(),
            'tenants' => Tenant::count(),
            'shipments' => FbaShipment::withoutGlobalScopes()->count(),
        ];

        $recentUsers = User::latest()->take(10)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers'));
    }

    public function system()
    {
        $stats = [
            'users' => User::count(),
            'tenants' => Tenant::count(),
            'shipments' => FbaShipment::withoutGlobalScopes()->count(),
            'amazon_accounts' => AmazonAccount::withoutGlobalScopes()->count(),
        ];

        $system = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server' => php_uname('s') . ' ' . php_uname('r'),
            'disk_free' => $this->formatBytes(disk_free_space('.')),
            'disk_total' => $this->formatBytes(disk_total_space('.')),
            'disk_percent' => round((1 - disk_free_space('.') / disk_total_space('.')) * 100),
            'memory_limit' => ini_get('memory_limit'),
            'memory_used' => $this->formatBytes(memory_get_usage(true)),
            'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'max_execution' => ini_get('max_execution_time') . 's',
            'cpu_load' => $this->getCpuLoad(),
            'ram' => $this->getRamUsage(),
        ];

        return view('admin.system', compact('stats', 'system'));
    }

    private function getCpuLoad(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = [];
            exec('wmic cpu get LoadPercentage /value', $output);
            foreach ($output as $line) {
                if (str_contains($line, 'LoadPercentage')) {
                    $val = trim(explode('=', $line)[1] ?? '0');
                    return ['percent' => (int) $val, 'cores' => (int) shell_exec('wmic cpu get NumberOfCores /value') ?: 1];
                }
            }
            return ['percent' => 0, 'cores' => 1];
        }

        $load = sys_getloadavg();
        $cores = (int) trim(shell_exec('nproc') ?: '1');
        return [
            'percent' => round(($load[0] / $cores) * 100),
            'cores' => $cores,
            'load_1' => $load[0] ?? 0,
            'load_5' => $load[1] ?? 0,
            'load_15' => $load[2] ?? 0,
        ];
    }

    private function getRamUsage(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = [];
            exec('wmic OS get TotalVisibleMemorySize,FreePhysicalMemory /value', $output);
            $total = $free = 0;
            foreach ($output as $line) {
                if (str_contains($line, 'TotalVisibleMemorySize')) {
                    $total = (int) trim(explode('=', $line)[1]);
                }
                if (str_contains($line, 'FreePhysicalMemory')) {
                    $free = (int) trim(explode('=', $line)[1]);
                }
            }
            $used = $total - $free;
            return [
                'total' => $this->formatBytes($total * 1024),
                'used' => $this->formatBytes($used * 1024),
                'free' => $this->formatBytes($free * 1024),
                'percent' => $total > 0 ? round(($used / $total) * 100) : 0,
            ];
        }

        $memInfo = [];
        if (is_readable('/proc/meminfo')) {
            $lines = file('/proc/meminfo');
            foreach ($lines as $line) {
                $parts = explode(':', $line);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $val = (int) trim(str_replace('kB', '', $parts[1]));
                    $memInfo[$key] = $val * 1024;
                }
            }
        }
        $total = $memInfo['MemTotal'] ?? 0;
        $available = $memInfo['MemAvailable'] ?? ($memInfo['MemFree'] ?? 0);
        $used = $total - $available;

        return [
            'total' => $this->formatBytes($total),
            'used' => $this->formatBytes($used),
            'free' => $this->formatBytes($available),
            'percent' => $total > 0 ? round(($used / $total) * 100) : 0,
        ];
    }

    private function formatBytes($bytes)
    {
        $bytes = max(0, (int) $bytes);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}
