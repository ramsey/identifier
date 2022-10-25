<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier;

enum Comparison
{
    case Equal;
    case GreaterThan;
    case GreaterThanOrEqualTo;
    case LessThan;
    case LessThanOrEqualTo;
    case NotEqual;
}
