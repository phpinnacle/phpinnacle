<?php

namespace PHPinnacle\Tempo\Calendar;

use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class CalendarRecordAction extends Action
{
    public function toModalHtmlable(): Htmlable
    {
        $html = sprintf(
            '<template x-teleport="body" wire:partial="action-modals.%s">%s</template>',
            $this->getNestingIndex(),
            $this->renderModal()->render(),
        );

        return new HtmlString($html);
    }

    /** @return array<mixed> */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        if ($parameterName !== 'event') {
            return parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName);
        }

        /** @var array{id: string, title: string, start: string, end: string|null, allDay: bool, viewable: bool, color: string|null, url: string|null} $event */
        $event = $this->getArguments()['event'];

        return [CalendarEvent::fromArray($event)];
    }
}
