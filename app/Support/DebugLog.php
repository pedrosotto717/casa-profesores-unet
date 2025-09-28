<?php

namespace App\Support;

/**
 * In-memory debug log collector for R2 diagnostics.
 * 
 * This class provides a simple way to collect debug information
 * during file upload operations and return it to the frontend.
 */
final class DebugLog
{
    private array $events = [];

    /**
     * Add a debug event with message and context.
     * 
     * @param string $msg The debug message
     * @param array $ctx Additional context data (never includes secrets)
     * @return void
     */
    public function add(string $msg, array $ctx = []): void
    {
        $this->events[] = [
            't' => now()->toISOString(),
            'msg' => $msg,
            'ctx' => $ctx
        ];
    }

    /**
     * Get all collected debug events.
     * 
     * @return array Array of debug events
     */
    public function all(): array
    {
        return $this->events;
    }

    /**
     * Clear all debug events.
     * 
     * @return void
     */
    public function clear(): void
    {
        $this->events = [];
    }

    /**
     * Get the number of debug events collected.
     * 
     * @return int Number of events
     */
    public function count(): int
    {
        return count($this->events);
    }
}
