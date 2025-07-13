<?php

declare(strict_types=1);

namespace MiniDI;
enum Scope: string
{
    case SINGLETON = 'singleton';
    case TRANSIENT = 'transient';
}