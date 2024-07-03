<?php

declare(strict_types=1);

namespace Koriym\VarType;

use PHPUnit\Framework\TestCase;

class VarTypeTest extends TestCase
{
    protected VarType $varType;

    protected function setUp(): void
    {
        $this->varType = new VarType();
    }

    public function testIsInstanceOfVarType(): void
    {
        $actual = $this->varType;
        $this->assertInstanceOf(VarType::class, $actual);
    }
}
