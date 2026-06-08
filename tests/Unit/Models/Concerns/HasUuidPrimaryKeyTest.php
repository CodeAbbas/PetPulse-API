<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class HasUuidPrimaryKeyTest extends TestCase
{
    #[Test]
    public function it_declares_string_key_type(): void
    {
        $model = new class extends Model
        {
            use HasUuidPrimaryKey;
        };

        $this->assertSame('string', $model->getKeyType());
    }

    #[Test]
    public function it_disables_auto_incrementing(): void
    {
        $model = new class extends Model
        {
            use HasUuidPrimaryKey;
        };

        $this->assertFalse($model->getIncrementing());
    }
}