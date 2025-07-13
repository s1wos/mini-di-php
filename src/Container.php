<?php
declare(strict_types=1);

namespace MiniDI;

use MiniDI\Exceptions\{CircularDependencyException, ContainerException, NotFoundException};
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

final class Container implements ContainerInterface
{
    /** @var array<string, Definition> */
    private array $definitions = [];

    /** @var array<string, object>  глобальные singleton-ы */
    private array $singletons = [];

    /** @var string[]  стек для обнаружения циклов */
    private array $stack = [];

    /** @var array<string, object>  кэш «внутри одного графа» */
    private array $graphCache = [];

    /* ------------------------------------------------ регистрация */

    public function bind(string $abstract, callable|string|null $concrete = null, Scope $scope = Scope::TRANSIENT): void
    {
        $this->definitions[$abstract] = new Definition($abstract, $concrete, $scope);
    }

    public function singleton(string $abstract, callable|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, Scope::SINGLETON);
    }

    /* ------------------------------------------------ PSR-11 */

    public function get(string $id): mixed   { return $this->make($id); }
    public function has(string $id): bool    { return isset($this->definitions[$id]) || class_exists($id); }

    /* ------------------------------------------------ основной метод */

    public function make(string $id): mixed
    {
        /** корневой ли это вызов (стек пуст)? */
        $isRoot = \count($this->stack) === 0;
        if ($isRoot) {
            $this->graphCache = [];           // начинаем новый граф – очищаем кэш
        }

        /* 0. объект уже создан в рамках текущего графа */
        if (isset($this->graphCache[$id])) {
            return $this->graphCache[$id];
        }

        /* 1. глобальный singleton */
        if (isset($this->singletons[$id])) {
            return $this->singletons[$id];
        }

        /* 2. защита от циклических зависимостей */
        if (\in_array($id, $this->stack, true)) {
            throw new CircularDependencyException("Circular dependency: {$id}");
        }
        $this->stack[] = $id;

        /* 3. создаём объект */
        $def    = $this->definitions[$id] ?? new Definition($id);
        $object = $this->build($def);

        \array_pop($this->stack);

        /* 4. кладём в кэши ------------------------------------------- */
        $this->graphCache[$id] = $object;            // абстрактный ID

// если ID ≠ реальный класс — сохраняем и по классу,
// чтобы повторный запрос по Foo::class вернул тот же объект
        $realClass = \get_class($object);
        if ($realClass !== $id) {
            $this->graphCache[$realClass] = $object;
        }

        if ($def->scope === Scope::SINGLETON) {
            $this->singletons[$id] = $object;
        }

        /* 5. корневой вызов завершён – чистим кэш */
        if ($isRoot) {
            $this->graphCache = [];
        }

        return $object;
    }

    /** @throws ContainerException */
    private function build(Definition $def): object
    {
        $concrete = $def->concrete ?? $def->abstract;

        if (\is_callable($concrete)) {
            return $concrete($this);
        }
        if (!\class_exists($concrete)) {
            throw new NotFoundException("Class {$concrete} not found");
        }

        try {
            $ref = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new ContainerException("Reflection error for {$concrete}", 0, $e);
        }
        if (!$ref->isInstantiable()) {
            throw new ContainerException("Class {$concrete} is not instantiable");
        }

        $ctor = $ref->getConstructor();
        if ($ctor === null || $ctor->getNumberOfParameters() === 0) {
            return $ref->newInstance();
        }

        $deps = [];
        foreach ($ctor->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $deps[] = $this->make($type->getName());
                continue;
            }
            if ($param->isDefaultValueAvailable()) {
                $deps[] = $param->getDefaultValue();
                continue;
            }
            throw new ContainerException(
                \sprintf('Cannot resolve parameter %s of %s::__construct()', $param->getName(), $concrete)
            );
        }

        return $ref->newInstanceArgs($deps);
    }
}