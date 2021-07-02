<?php

namespace Strategy;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Pure;
use PHPUnit\Framework\TestCase;
use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\DefaultStrategy;
use Rikudou\Unleash\DTO\Strategy;
use Rikudou\Unleash\Enum\Stickiness;
use Rikudou\Unleash\Exception\MissingArgumentException;
use Rikudou\Unleash\Stickiness\MurmurHashCalculator;
use Rikudou\Unleash\Strategy\GradualRolloutStrategyHandler;

final class GradualRolloutStrategyHandlerTest extends TestCase
{
    /**
     * @var GradualRolloutStrategyHandler
     */
    private $instance;

    protected function setUp(): void
    {
        $this->instance = new GradualRolloutStrategyHandler(new MurmurHashCalculator());
    }

    public function testSupports()
    {
        self::assertFalse($this->instance->supports(new DefaultStrategy('default', [])));
        self::assertTrue($this->instance->supports(new DefaultStrategy('flexibleRollout', [])));
        self::assertFalse($this->instance->supports(new DefaultStrategy('remoteAddress', [])));
        self::assertFalse($this->instance->supports(new DefaultStrategy('userWithId', [])));
        self::assertFalse($this->instance->supports(new DefaultStrategy('nonexistent', [])));
    }

    public function testIsEnabled()
    {
        // no exception should be thrown
        $this->instance->isEnabled($this->createStrategy(), new UnleashContext());

        self::assertFalse($this->instance->isEnabled($this->createStrategy(50), new UnleashContext('123')));
        self::assertFalse($this->instance->isEnabled($this->createStrategy(50), new UnleashContext('456')));
        self::assertTrue($this->instance->isEnabled($this->createStrategy(50), new UnleashContext('852')));

        self::assertFalse($this->instance->isEnabled($this->createStrategy(50), new UnleashContext('123')));
        self::assertFalse($this->instance->isEnabled($this->createStrategy(50), new UnleashContext('456')));
        self::assertTrue($this->instance->isEnabled($this->createStrategy(50), new UnleashContext('852')));

        self::assertFalse(
            $this->instance->isEnabled(
                $this->createStrategy(50),
                new UnleashContext('123', null, '852')
            )
        );
        self::assertFalse(
            $this->instance->isEnabled(
                $this->createStrategy(50),
                new UnleashContext('456', null, '852')
            )
        );
        self::assertTrue(
            $this->instance->isEnabled(
                $this->createStrategy(50),
                new UnleashContext('852', null, '123')
            )
        );

        try {
            $this->instance->isEnabled(
                $this->createStrategy(100, Stickiness::USER_ID),
                new UnleashContext(),
            );
            $this->fail('Expected exception of class ' . MissingArgumentException::class);
        } catch (MissingArgumentException $e) {
        }

        try {
            $this->instance->isEnabled(
                $this->createStrategy(100, Stickiness::USER_ID),
                new UnleashContext(null, null, 'test'),
            );
            $this->fail('Expected exception of class ' . MissingArgumentException::class);
        } catch (MissingArgumentException $e) {
        }

        self::assertFalse(
            $this->instance->isEnabled(
                $this->createStrategy(50, Stickiness::USER_ID),
                new UnleashContext('456')
            )
        );
        self::assertTrue(
            $this->instance->isEnabled(
                $this->createStrategy(50, Stickiness::USER_ID),
                new UnleashContext('852')
            )
        );

        try {
            $this->instance->isEnabled(
                $this->createStrategy(100, Stickiness::SESSION_ID),
                new UnleashContext(),
            );
            $this->fail('Expected exception of class ' . MissingArgumentException::class);
        } catch (MissingArgumentException $e) {
        }

        try {
            $this->instance->isEnabled(
                $this->createStrategy(100, Stickiness::SESSION_ID),
                new UnleashContext('test'),
            );
            $this->fail('Expected exception of class ' . MissingArgumentException::class);
        } catch (MissingArgumentException $e) {
        }

        self::assertFalse(
            $this->instance->isEnabled(
                $this->createStrategy(50, Stickiness::SESSION_ID),
                new UnleashContext(null, null, '456')
            )
        );
        self::assertTrue(
            $this->instance->isEnabled(
                $this->createStrategy(50, Stickiness::SESSION_ID),
                new UnleashContext(null, null, '852')
            )
        );

        $this->instance->isEnabled($this->createStrategy(100, Stickiness::RANDOM), new UnleashContext());
    }

    #[Pure]
    private function createStrategy(
        int $percentage = 100,
        #[ExpectedValues(valuesFromClass: Stickiness::class)]
        $stickiness = Stickiness::DEFAULT
    ): Strategy {
        return new DefaultStrategy('flexibleRollout', [
            'stickiness' => $stickiness,
            'groupId'=> 'default',
            'rollout' => $percentage,
        ]);
    }
}