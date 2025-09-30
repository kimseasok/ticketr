<?php

namespace App\Support\Tenancy;

class TenantContext
{
    private ?int $tenantId = null;

    private ?int $brandId = null;

    public function setTenantId(?int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function setBrandId(?int $brandId): void
    {
        $this->brandId = $brandId;
    }

    public function getBrandId(): ?int
    {
        return $this->brandId;
    }

    public function clear(): void
    {
        $this->tenantId = null;
        $this->brandId = null;
    }
}
