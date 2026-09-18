<?php

namespace App\Tenancy;

use App\Repositories\WorkspaceRepository;

class TenantResolver
{
    public function __construct(
        private WorkspaceRepository $workspaceRepo,
        private string $baseDomain,
        private string $defaultSlug,
        private string $appEnv = 'local'
    ) {}

    public function resolve(?string $host = null, ?string $debugOverride = null): ?Workspace
    {
        // 1. Debug / Local query override
        if ($this->appEnv === 'local' && !empty($debugOverride)) {
            $ws = $this->workspaceRepo->findBySlug($debugOverride);
            if ($ws) return $ws;
        }

        if (empty($host)) {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        }

        // Strip port if present (e.g. 127.0.0.1:8000 -> 127.0.0.1)
        $cleanHost = strtolower(explode(':', $host)[0]);

        // 2. Exact match on custom_domain
        $ws = $this->workspaceRepo->findByCustomDomain($cleanHost);
        if ($ws) {
            return $ws;
        }

        // 3. Subdomain extraction against base_domain (e.g. cliente1.vendedorsincero.pro)
        $base = strtolower($this->baseDomain);
        if (str_ends_with($cleanHost, '.' . $base)) {
            $subdomain = substr($cleanHost, 0, -strlen('.' . $base));
            if (!empty($subdomain)) {
                $ws = $this->workspaceRepo->findBySlug($subdomain);
                if ($ws) return $ws;
            }
        }

        // 4. If cleanHost is itself a slug (e.g. in test env or header)
        $ws = $this->workspaceRepo->findBySlug($cleanHost);
        if ($ws) {
            return $ws;
        }

        // 5. Default workspace fallback (especially for localhost / dev server)
        return $this->workspaceRepo->findBySlug($this->defaultSlug);
    }
}
