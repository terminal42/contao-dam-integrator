<?php

declare(strict_types=1);

namespace Terminal42\ContaoDamIntegrator\Integration\Dto;

class DownloadResult
{
    /**
     * @param resource $stream
     */
    private function __construct(
        private readonly bool $successful,
        private readonly string $identifier,
        private readonly string|null $hash = null,
        private $stream = null,
        private readonly string|null $path = null,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getHash(): string
    {
        $this->ensureSuccessful();

        return $this->hash;
    }

    public function getPath(): string
    {
        $this->ensureSuccessful();

        return $this->path;
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        $this->ensureSuccessful();

        return $this->stream;
    }

    /**
     * @param resource $stream
     */
    public static function successful(string $identifier, string $hash, $stream, string $path): self
    {
        return new self(true, $identifier, $hash, $stream, $path);
    }

    public static function failed(string $identifier): self
    {
        return new self(false, $identifier);
    }

    private function ensureSuccessful(): void
    {
        if (!$this->successful) {
            throw new \LogicException('Import result must be successful in order to call this method.');
        }
    }
}
