<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\BiometricCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BiometricCalculatorTest extends TestCase
{
    private BiometricCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new BiometricCalculator();
    }

    // ─── BMI ────────────────────────────────────────────────────

    #[Test]
    public function it_computes_bmi_for_known_values(): void
    {
        // 30 kg, 60 cm → 30 / (0.6)^2 = 30 / 0.36 = 83.33
        $this->assertSame(83.33, $this->calculator->bmi(30.0, 60.0));
    }

    #[Test]
    public function it_computes_bmi_for_a_second_known_value(): void
    {
        // 20 kg, 50 cm → 20 / (0.5)^2 = 20 / 0.25 = 80.0
        $this->assertSame(80.0, $this->calculator->bmi(20.0, 50.0));
    }

    #[Test]
    public function bmi_returns_null_for_null_weight(): void
    {
        $this->assertNull($this->calculator->bmi(null, 60.0));
    }

    #[Test]
    public function bmi_returns_null_for_null_height(): void
    {
        $this->assertNull($this->calculator->bmi(30.0, null));
    }

    #[Test]
    public function bmi_returns_null_for_zero_height_preventing_divide_by_zero(): void
    {
        $this->assertNull($this->calculator->bmi(30.0, 0.0));
    }

    #[Test]
    public function bmi_returns_null_for_negative_values(): void
    {
        $this->assertNull($this->calculator->bmi(-5.0, 60.0));
        $this->assertNull($this->calculator->bmi(30.0, -10.0));
    }

    // ─── BMR ────────────────────────────────────────────────────

    #[Test]
    public function it_computes_bmr_for_known_values(): void
    {
        // 70 * 10^0.75 = 70 * 5.6234... = 393.64
        $this->assertSame(393.64, $this->calculator->bmr(10.0));
    }

    #[Test]
    public function it_computes_bmr_for_a_second_known_value(): void
    {
        // 70 * 30^0.75 = 70 * 12.8169... = 897.18
        $this->assertSame(897.3, $this->calculator->bmr(30.0));
    }

    #[Test]
    public function bmr_returns_null_for_null_weight(): void
    {
        $this->assertNull($this->calculator->bmr(null));
    }

    #[Test]
    public function bmr_returns_null_for_zero_weight(): void
    {
        $this->assertNull($this->calculator->bmr(0.0));
    }

    #[Test]
    public function bmr_returns_null_for_negative_weight(): void
    {
        $this->assertNull($this->calculator->bmr(-15.0));
    }
}