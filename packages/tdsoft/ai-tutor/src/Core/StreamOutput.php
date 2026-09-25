<?php

namespace TDSoft\AiTutor\Core;

use Closure;

final class StreamOutput
{
    private ?Closure $listener = null;

    public function active(): bool
    {
        return $this->listener !== null;
    }

    public function emit(string $text): void
    {
        if ($this->listener !== null) {
            ($this->listener)($text);
        }
    }

    public function during(Closure $listener, Closure $work): mixed
    {
        $previous = $this->listener;
        $this->listener = $listener;
        try {
            return $work();
        } finally {
            $this->listener = $previous;
        }
    }
}
