<?php

declare(strict_types=1);

namespace Phpactor;

use DTL\WorseReflection\Source;

class CodeContext
{
    private $source;
    private $offset;

    private function __construct()
    {
    }

    public static function create(Source $source, int $offset)
    {
        $context = new self();
        $context->source = $source;
        $context->offset = $offset;

        return $context;
    }

    public function getSource(): Source
    {
        return $this->source;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }
}
