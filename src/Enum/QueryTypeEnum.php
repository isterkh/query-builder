<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Enum;

enum QueryTypeEnum: string
{
    case SELECT = 'select';
    case INSERT = 'insert';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case RAW = 'raw';
}
