<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\User;

class EmailTemplateService
{
    /**
     * Get a user's saved template for the given type, or fall back to the built-in default.
     * Pass null for $user to get the system (admin) template.
     */
    public function getTemplate(?User $user, string $type): array
    {
        $template = EmailTemplate::where('user_id', $user?->id)
            ->where('type', $type)
            ->first();

        if ($template) {
            return ['subject' => $template->subject, 'body' => $template->body];
        }

        return EmailTemplate::DEFAULTS[$type] ?? ['subject' => '', 'body' => ''];
    }

    /**
     * Replace {{tag}} placeholders in a string with values from $vars.
     */
    public function render(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value ?? '', $template);
        }

        return $template;
    }
}
