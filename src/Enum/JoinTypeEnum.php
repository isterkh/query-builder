<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Enum;

enum JoinTypeEnum: string
{
    case LEFT = 'left';
    case RIGHT = 'right';
    case INNER = 'inner';

}
