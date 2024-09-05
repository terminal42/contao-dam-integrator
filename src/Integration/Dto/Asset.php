<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration\Dto;

use ScriptFUSION\Byte\ByteFormatter;

class Asset
{
    public int|null $width = null;

    public int|null $height = null;

    public int|null $fileSize = null;

    public string|null $extension = null;

    public Thumbnail|null $thumbnail = null;

    public function __construct(
        public readonly string $identifier,
        public readonly string $hash,
        public readonly string $name,
    ) {
    }

    /**
     * @return array{identifier: string, hash: string, name: string, width: ?int, height: ?int, fileSize: ?int, extension: ?string, meta: string, thumb: array{url: string, alt: string}|null}
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
            'extension' => $this->extension,
            'meta' => $this->formatMeta(),
            'thumb' => $this->thumbnail?->toArray(),
        ];
    }

    public function formatMeta(): string
    {
        $meta = [];
        if (null !== $this->fileSize && $this->fileSize > 0) {
            $meta['size'] = (new ByteFormatter())->format($this->fileSize);
        }

        if (null !== $this->extension) {
            $meta[] = $this->extension;
        }

        if (null !== $this->width && null !== $this->height && $this->height > 0 && $this->width > 0) {
            $meta[] = \sprintf(
                '%s x %s px',
                $this->width,
                $this->height,
            );
        }

        return implode('; ', $meta);
    }
}
