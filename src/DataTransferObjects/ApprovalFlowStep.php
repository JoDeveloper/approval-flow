<?php

namespace Jodeveloper\ApprovalFlow\DataTransferObjects;

class ApprovalFlowStep
{
    public function __construct(
        public readonly string $permission,
        public readonly string $next,
        public readonly ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'permission' => $this->permission,
            'next' => $this->next,
            'metadata' => $this->metadata,
        ], fn ($value) => $value !== null);
    }
}
