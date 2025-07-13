<?php
declare(strict_types=1);

namespace MiniDI;

/**
 * Value-object описывающее связь «абстракция -> конкретная реализация».
 */
final class Definition
{
    /** @param callable|string|null $concrete */
    public function __construct(
        public readonly string $abstract,
        public readonly mixed  $concrete = null,
        public readonly Scope  $scope    = Scope::TRANSIENT,
    ) {}
}

