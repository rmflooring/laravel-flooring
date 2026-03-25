<?php

namespace App\Services;

use App\Models\SmsTemplate;

class SmsTemplateService
{
    /**
     * Get the body for the given type — saved template or built-in default.
     */
    public function getBody(string $type): string
    {
        $template = SmsTemplate::where('type', $type)->first();

        return $template?->body ?? SmsTemplate::DEFAULTS[$type] ?? '';
    }

    /**
     * Replace {{tag}} placeholders with values from $vars.
     */
    public function render(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value ?? '', $template);
        }

        return $template;
    }

    /**
     * Get and render a template in one call.
     */
    public function renderTemplate(string $type, array $vars): string
    {
        return $this->render($this->getBody($type), $vars);
    }
}
