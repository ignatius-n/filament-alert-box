<?php

namespace Agencetwogether\AlertBox\Concerns;

use Closure;
use Filament\Support\Concerns\EvaluatesClosures;

trait CanCustomizeColors
{
    use EvaluatesClosures;

    protected null | Closure | string $colorInfo = null;

    protected null | Closure | string $colorTip = null;

    protected null | Closure | string $colorSuccess = null;

    protected null | Closure | string $colorWarning = null;

    protected null | Closure | string $colorDanger = null;

    public function colorInfo(string | Closure $color): static
    {
        $this->colorInfo = $color;

        return $this;
    }

    public function colorTip(string | Closure $color): static
    {
        $this->colorTip = $color;

        return $this;
    }

    public function colorSuccess(string | Closure $color): static
    {
        $this->colorSuccess = $color;

        return $this;
    }

    public function colorWarning(string | Closure $color): static
    {
        $this->colorWarning = $color;

        return $this;
    }

    public function colorDanger(string | Closure $color): static
    {
        $this->colorDanger = $color;

        return $this;
    }

    public function getColorInfo(): ?string
    {
        return $this->evaluate($this->colorInfo);
    }

    public function getColorTip(): ?string
    {
        return $this->evaluate($this->colorTip);
    }

    public function getColorSuccess(): ?string
    {
        return $this->evaluate($this->colorSuccess);
    }

    public function getColorWarning(): ?string
    {
        return $this->evaluate($this->colorWarning);
    }

    public function getColorDanger(): ?string
    {
        return $this->evaluate($this->colorDanger);
    }
}
