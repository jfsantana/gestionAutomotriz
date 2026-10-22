<?php

namespace App\Domain\Companies\Contracts;

/**
 * Interface contractual para entidades con ámbito de pertenencia a una Empresa.
 * En FASE 0 define la firma base sin aplicar resolución automática ni scopes globales.
 */
interface CompanyScopedInterface
{
    /**
     * Obtiene el identificador de la empresa a la que pertenece la entidad.
     */
    public function getCompanyId(): ?int;
}
