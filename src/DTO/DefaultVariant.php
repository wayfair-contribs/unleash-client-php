<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\ExpectedValues;
use Unleash\Client\Enum\Stickiness;

final class DefaultVariant implements Variant
{
    /**
     * @param array<VariantOverride> $overrides
     */
    public function __construct(
        private readonly string $name,
        private readonly bool $enabled,
        private readonly int $weight = 0,
        #[ExpectedValues(valuesFromClass: Stickiness::class)]
        private readonly string $stickiness = Stickiness::DEFAULT,
        private readonly ?VariantPayload $payload = null,
        private readonly ?array $overrides = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPayload(): ?VariantPayload
    {
        return $this->payload;
    }

    /**
     * @phpstan-return array<string|bool|array<string>>
     */
    #[ArrayShape(['name' => 'string', 'enabled' => 'bool', 'payload' => 'mixed'])]
    public function jsonSerialize(): array
    {
        $result = [
            'name' => $this->name,
            'enabled' => $this->enabled,
        ];
        if ($this->payload !== null) {
            $result['payload'] = $this->payload->jsonSerialize();
            assert(is_array($result['payload']));
        }

        return $result;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return array<VariantOverride>
     */
    public function getOverrides(): array
    {
        return $this->overrides ?? [];
    }

    #[ExpectedValues(valuesFromClass: Stickiness::class)]
    public function getStickiness(): string
    {
        return $this->stickiness;
    }
}
