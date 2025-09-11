<?php

namespace Jodeveloper\ApprovalFlow\DataTransferObjects;

use Illuminate\Support\Facades\Gate;
use Jodeveloper\ApprovalFlow\Exceptions\ApprovalFlowException;

class ApprovalFlowStep
{
    public function __construct(
        public readonly ?string $permission,
        public readonly string $next,
        public readonly ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        $data = [
            'next' => $this->next,
            'metadata' => $this->metadata,
        ];

        // Only include permission if it's not null
        if ($this->permission !== null) {
            $data['permission'] = $this->permission;
        }

        return array_filter($data, fn ($value) => $value !== null);
    }

    public function validatePermission($model): void
    {
        // Skip validation if no permission is required
        if ($this->permission === null) {
            return;
        }

        // Check if permission is in camelCase
        if (! preg_match('/^[a-z][a-zA-Z]*$/', $this->permission)) {
            throw ApprovalFlowException::invalidPermission($this->permission, 'must be in camelCase');
        }

        // Check if permission is a method in the model's policy
        $policy = Gate::getPolicyFor($model);
        if (! $policy || ! method_exists($policy, $this->permission)) {
            throw ApprovalFlowException::invalidPermission($this->permission, 'not a valid policy method for '.get_class($model));
        }
    }
}
