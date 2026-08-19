<?php

namespace App\Services\Agent;

use App\Models\KnowledgeAgentToolAccess;
use App\Models\User;

/**
 * Single source of truth for "can this user's role(s) use this knowledge-agent tool /
 * see this knowledge category." Claude choosing to call a tool is never authorization —
 * every tool service must call canUseTool() itself before running its query, and
 * KnowledgeSearchService uses roleFilterFor() to exclude restricted entries before
 * they're even scored, not after.
 */
class KnowledgeAccessGate
{
    public function isAdmin(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * @return array<int, string>
     */
    public function userRoles(User $user): array
    {
        return $user->getRoleNames()->all();
    }

    public function canUseTool(User $user, string $toolName): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        $roles = $this->userRoles($user);
        if (empty($roles)) {
            return false;
        }

        return KnowledgeAgentToolAccess::whereIn('role', $roles)
            ->where('tool_name', $toolName)
            ->where('allowed', true)
            ->exists();
    }

    /**
     * Roles to filter knowledge_entries.visible_to_roles against, or null for "no
     * filter — sees everything" (admin only).
     *
     * @return array<int, string>|null
     */
    public function roleFilterFor(User $user): ?array
    {
        if ($this->isAdmin($user)) {
            return null;
        }

        return $this->userRoles($user);
    }

    /**
     * The clean, structure-free result every tool service returns when a user's role
     * doesn't have access — never an exception/error that could leak schema details.
     */
    public function unauthorizedResult(): array
    {
        return [
            'authorized' => false,
            'message' => 'You do not have access to this information. Ask an admin if you need it.',
        ];
    }
}
