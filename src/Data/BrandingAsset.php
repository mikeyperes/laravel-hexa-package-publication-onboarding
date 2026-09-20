<?php

namespace hexa_package_publication_onboarding\Data;

use InvalidArgumentException;

final readonly class BrandingAsset
{
    public function __construct(
        public string $role,
        public string $inputReference,
        public string $filename,
        public string $mimeType,
        public string $sha256,
        public ?int $width = null,
        public ?int $height = null,
        public string $altText = '',
        public string $caption = '',
    ) {
        if (! in_array($this->role, ['logo', 'icon'], true)) {
            throw new InvalidArgumentException('Branding assets must use the logo or icon role.');
        }
        if (trim($this->inputReference) === '' || mb_strlen($this->inputReference) > 255) {
            throw new InvalidArgumentException('An opaque branding input reference is required.');
        }
        if (trim($this->filename) === '' || preg_match('/[\x00-\x1F\x7F]/', $this->filename) === 1) {
            throw new InvalidArgumentException('A safe branding filename is required.');
        }
        if (preg_match('/^[a-f0-9]{64}$/', strtolower($this->sha256)) !== 1) {
            throw new InvalidArgumentException('A SHA-256 digest is required for each branding asset.');
        }
        if (($this->width !== null && $this->width < 1) || ($this->height !== null && $this->height < 1)) {
            throw new InvalidArgumentException('Branding dimensions must be positive when supplied.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'input_reference' => $this->inputReference,
            'filename' => $this->filename,
            'mime_type' => strtolower(trim($this->mimeType)),
            'sha256' => strtolower($this->sha256),
            'width' => $this->width,
            'height' => $this->height,
            'alt_text' => $this->altText,
            'caption' => $this->caption,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['role'] ?? ''),
            (string) ($data['input_reference'] ?? ''),
            (string) ($data['filename'] ?? ''),
            (string) ($data['mime_type'] ?? ''),
            (string) ($data['sha256'] ?? ''),
            isset($data['width']) ? (int) $data['width'] : null,
            isset($data['height']) ? (int) $data['height'] : null,
            (string) ($data['alt_text'] ?? ''),
            (string) ($data['caption'] ?? ''),
        );
    }
}
