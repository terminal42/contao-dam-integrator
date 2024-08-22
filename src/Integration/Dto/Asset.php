<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration\Dto;

use ScriptFUSION\Byte\ByteFormatter;

class Asset
{
    public int|null $width = null;

    public int|null $height = null;

    public int|null $fileSize = null;

    public Thumbnail|null $thumbnail = null;

    public function __construct(
        public readonly string $identifier,
        public readonly string $hash,
        public readonly string $name,
    ) {
    }

    /**
     * @return array{identifier: string, hash: string, name: string, width: ?int, height: ?int, fileSize: ?int, meta: string, thumb: array{url: string, alt: string}|null}
     */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'hash' => $this->hash,
            'name' => $this->name,
            'width' => $this->width,
            'height' => $this->height,
            'fileSize' => $this->fileSize,
            'meta' => $this->formatMeta(),
            'thumb' => $this->thumbnail?->toArray(),
        ];
    }

    public function formatMeta(): string
    {
        $meta = [];
        if (null !== $this->fileSize) {
            $meta['size'] = (new ByteFormatter())->format($this->fileSize);
        }

        if (null !== $this->width && null !== $this->height) {
            $meta[] = \sprintf(
                '(%sx%s px)',
                $this->width,
                $this->height,
            );
        }

        return implode(' ', $meta);
    }
}
