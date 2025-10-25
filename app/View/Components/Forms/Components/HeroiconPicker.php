<?php
namespace App\View\Components\Forms\Components;

use Filament\Forms\Components\Field;

class HeroiconPicker extends Field
{
    protected string $view            = 'components.forms.components.heroicon-picker';
    protected array|\Closure $icons = [];

    //public static $label = 'MyIcon';

    public function icons(array | \Closure $icons): static
    {
        $this->icons = $icons;
        return $this;
    }

    public function getIcons(): array
    {
        return $this->evaluate($this->icons);
    }
}
