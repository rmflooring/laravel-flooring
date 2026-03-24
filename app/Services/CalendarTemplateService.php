<?php

namespace App\Services;

use App\Models\CalendarTemplate;

class CalendarTemplateService
{
    /**
     * Get the saved template for a type, falling back to the built-in default.
     * Returns ['title_template' => ..., 'notes_template' => ...].
     */
    public function getTemplate(string $type): array
    {
        $saved    = CalendarTemplate::where('type', $type)->first();
        $defaults = CalendarTemplate::DEFAULTS[$type] ?? ['title_template' => '', 'notes_template' => ''];

        return [
            'title_template' => $saved?->title_template ?? $defaults['title_template'],
            'notes_template' => $saved?->notes_template ?? $defaults['notes_template'],
        ];
    }

    /**
     * Replace {{tag}} placeholders in a template string with values from $vars.
     * Unknown tags are left as-is.
     */
    public function render(string $template, array $vars): string
    {
        $search  = array_map(fn($k) => '{{' . $k . '}}', array_keys($vars));
        $replace = array_values($vars);

        return str_replace($search, $replace, $template);
    }

    /**
     * Render both title and notes from a template type and a vars array.
     * Returns ['title' => ..., 'notes' => ...].
     */
    public function renderTemplate(string $type, array $vars): array
    {
        $tpl = $this->getTemplate($type);

        return [
            'title' => $this->render($tpl['title_template'], $vars),
            'notes' => $this->render($tpl['notes_template'], $vars),
        ];
    }
}
