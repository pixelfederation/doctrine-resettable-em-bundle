<?php

declare(strict_types=1);

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

    private function __construct(
        private readonly string $type,
    ) {
    }

    public static function create(string $type): self
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
