<?php

declare(strict_types=1);

/*
 * @author mfris
 */

namespace PixelFederation\DoctrineResettableEmBundle\DBAL\Connection\FailoverAware;

use InvalidArgumentException;

final class ConnectionType
{
    public const WRITER = 'writer';
    public const READER = 'reader';

    private const ALLOWED_TYPES = [
        self::WRITER,
        self::READER,
    ];

    private string $type;

    private function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function create(string $type): ConnectionType
    {
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Invalid connection type %s.', $type));
        }

        return new self($type);
    }

    public function isWriter(): bool
    {
        return $this->type === self::WRITER;
    }
}
