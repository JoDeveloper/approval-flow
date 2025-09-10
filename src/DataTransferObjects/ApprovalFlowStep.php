<?php

namespace Jodeveloper\ApprovalFlow\DataTransferObjects;

use Illuminate\Support\Facades\Gate;
use Jodeveloper\ApprovalFlow\Exceptions\ApprovalFlowException;

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

    public function validatePermission($model): void
    {
        // Check if permission is in camelCase
        if (!preg_match('/^[a-z][a-zA-Z]*$/', $this->permission)) {
            throw ApprovalFlowException::invalidPermission($this->permission, 'must be in camelCase');
        }

        // Check if permission is a method in the model's policy
        $policy = Gate::getPolicyFor($model);
        if (!$policy || !method_exists($policy, $this->permission)) {
            throw ApprovalFlowException::invalidPermission($this->permission, 'not a valid policy method for ' . get_class($model));
        }
    }
}
