<?php

namespace App\Services\Agent;

/**
 * Thrown when a Claude tool call has invalid input. Callers catch this and turn
 * it into an `is_error: true` tool_result instead of failing the whole task.
 */
class AgentToolValidationException extends \RuntimeException
{
}
