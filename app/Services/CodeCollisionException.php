<?php

namespace App\Services;

/**
 * Two different records claim one identifier code.
 *
 * The scheme makes some codes name-only (a property, a data template, a construction
 * object, a reference document) on the promise that those names are unique. When that
 * promise is broken the platform must say so rather than pick a winner: silently
 * resolving to one of them would hand out an identifier that means different things to
 * different readers. Callers catch this and show the report.
 *
 * NOTE: the colliding code is held in $uriCode, not $code — \Exception already defines
 * $code as its (int) error code, and redeclaring it is a fatal error.
 */
class CodeCollisionException extends \RuntimeException
{
    public string $entity;
    public string $uriCode;

    /** @var array<int,string> human-readable descriptions of the colliding records */
    public array $records;

    public function __construct(string $entity, string $uriCode, array $records)
    {
        $this->entity = $entity;
        $this->uriCode = $uriCode;
        $this->records = $records;

        parent::__construct(
            "The identifier {$entity}/{$uriCode} is claimed by " . count($records)
            . " different records, so it cannot be resolved:\n - " . implode("\n - ", $records)
        );
    }
}
