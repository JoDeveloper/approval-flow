<?php

namespace Jodeveloper\ApprovalFlow\Exceptions;

use Exception;

class ApprovalFlowException extends Exception
{
    public static function invalidStatus(string $status, string $model): self
    {
        return new self("Invalid approval status '{$status}' for model {$model}");
    }

    public static function unauthorizedAction(string $action, string $model): self
    {
        return new self("Unauthorized to {$action} {$model}");
    }

    public static function invalidFlowStep(string $status): self
    {
        return new self("Invalid flow step configuration for status '{$status}'");
    }

    public static function missingStatusEnum(string $model): self
    {
        return new self("Model {$model} must implement getStatusEnum() method");
    }

    public static function invalidPermission(string $permission, string $reason): self
    {
        return new self("Invalid permission '{$permission}': {$reason}");
    }
}
