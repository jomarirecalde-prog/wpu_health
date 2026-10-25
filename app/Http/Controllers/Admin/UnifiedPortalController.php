<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UnifiedPortalController extends Controller
{
    public function __invoke(Request $request, ?string $path = null): mixed
    {
        $path = $path === null || $path === '' ? 'admin/admin.php' : ltrim(str_replace('\\', '/', $path), '/');

        $base = realpath(base_path('unified_portal'));
        if ($base === false) {
            abort(503, 'Unified portal is not installed.');
        }

        $candidate = $base.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
        $full = realpath($candidate);
        if ($full === false || ! str_starts_with($full, $base)) {
            abort(404);
        }

        if (! is_readable($full) || ! is_file($full)) {
            abort(404);
        }

        if (strtolower(pathinfo($full, PATHINFO_EXTENSION)) !== 'php') {
            return new BinaryFileResponse($full);
        }

        if (! defined('WPU_LARAVEL_BRIDGE')) {
            define('WPU_LARAVEL_BRIDGE', true);
        }
        if (! defined('WPU_LARAVEL_ADMIN_USER')) {
            $bridgeUser = $request->user('admin')?->username
                ?? $request->user('physician')?->email
                ?? '';
            define('WPU_LARAVEL_ADMIN_USER', (string) $bridgeUser);
        }
        $workspaceRoute = (string) ($request->route()?->getName() ?? 'admin.workspace');
        if (! defined('WPU_LARAVEL_WORKSPACE_ROUTE')) {
            define('WPU_LARAVEL_WORKSPACE_ROUTE', $workspaceRoute);
        }
        if (! defined('WPU_LARAVEL_LOGOUT_URL')) {
            $logoutUrl = $workspaceRoute === 'physician.workspace'
                ? route('logout')
                : url('/admin/workspace/exit');
            define('WPU_LARAVEL_LOGOUT_URL', $logoutUrl);
        }
        if (! defined('WPU_PORTAL_INDEX_URL')) {
            define('WPU_PORTAL_INDEX_URL', url('/portal/index.php'));
        }

        $cwd = getcwd();
        chdir(dirname($full));
        $output = '';
        try {
            ob_start();
            try {
                include $full;
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
            $buffered = ob_get_clean();
            $output = $buffered !== false ? $buffered : '';
        } finally {
            if ($cwd !== false) {
                chdir($cwd);
            }
        }

        $trimmed = ltrim($output);
        $contentType = (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '['))
            ? 'application/json; charset=UTF-8'
            : 'text/html; charset=UTF-8';

        return response($output, 200)->header('Content-Type', $contentType);
    }
}
