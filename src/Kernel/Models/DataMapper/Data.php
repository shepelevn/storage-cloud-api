<?php

declare(strict_types=1);

namespace Kernel\Models\DataMapper;

use LogicException;

abstract class Data
{
    /**
     * @param DataValue $value
     **/
    public function __construct(public mixed $value)
    {
    }

    /**
     * @return SimpleValue
     **/
    public function simpleValue(): mixed
    {
        switch (gettype($this->value)) {
            case 'object':
                switch (get_class($this->value)) {
                    case 'DateTimeImmutable':
                        return $this->value->format('Y-m-d H:i:s');
                    default:
                        throw new LogicException('Unknown value class');
                }
                // no break
            case 'boolean':
                return (int) $this->value;
            default:
                return $this->value;
        }
    }
}
