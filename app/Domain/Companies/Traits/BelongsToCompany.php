<?php

namespace App\Domain\Companies\Traits;

/**
 * Trait base para entidades asociadas a una empresa.
 * En FASE 0 provee el contrato estructural de asignación y consulta de company_id
 * sin activar global scopes, resolución mágica ni middlewares.
 */
trait BelongsToCompany
{
    /**
     * Obtiene el ID de la empresa asociada.
     */
    public function getCompanyId(): ?int
    {
        return $this->company_id ?? null;
    }

    /**
     * Asigna la entidad a una empresa específica.
     */
    public function setCompanyId(?int $companyId): self
    {
        $this->company_id = $companyId;
        return $this;
    }
}

