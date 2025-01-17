<?php

namespace Unleash\Client\Metrics;

use DateTimeInterface;
use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;
use LogicException;

/**
 * @internal
 */
final class MetricsBucket implements JsonSerializable
{
    /**
     * @var array<MetricsBucketToggle>
     */
    private array $toggles = [];

    public function __construct(
        private readonly DateTimeInterface $startDate,
        private ?DateTimeInterface $endDate = null,
    ) {
    }

    public function addToggle(MetricsBucketToggle $toggle): self
    {
        $this->toggles[] = $toggle;

        return $this;
    }

    public function getStartDate(): DateTimeInterface
    {
        return $this->startDate;
    }

    public function setEndDate(?DateTimeInterface $endDate): MetricsBucket
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    #[ArrayShape(['start' => 'string', 'stop' => 'string', 'toggles' => 'array'])]
    public function jsonSerialize(): array
    {
        $togglesArray = [];

        if ($this->endDate === null) {
            throw new LogicException('Cannot serialize incomplete bucket');
        }

        foreach ($this->toggles as $toggle) {
            $featureName = $toggle->getFeature()->getName();
            if (!isset($togglesArray[$featureName])) {
                $togglesArray[$featureName] = [
                    'yes' => 0,
                    'no' => 0,
                ];
            }

            $updateField = $toggle->isSuccess() ? 'yes' : 'no';
            ++$togglesArray[$featureName][$updateField];

            if ($toggle->getVariant() !== null) {
                $variant = $toggle->getVariant();
                $togglesArray[$featureName]['variants'][$variant->getName()] ??= 0;
                ++$togglesArray[$featureName]['variants'][$variant->getName()];
            }
        }

        return [
            'start' => $this->startDate->format('c'),
            'stop' => $this->endDate->format('c'),
            'toggles' => $togglesArray,
        ];
    }
}
