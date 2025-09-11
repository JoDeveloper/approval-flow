<?php

namespace Jodeveloper\ApprovalFlow\Tests\Enums;

use Jodeveloper\ApprovalFlow\Contracts\ApprovalStatusInterface;
use Jodeveloper\ApprovalFlow\DataTransferObjects\ApprovalFlowStep;

enum TestStatus: string implements ApprovalStatusInterface
{
    case DRAFT = 'DRAFT';
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';

    public static function getApprovalFlow(): array
    {
        return [
            self::DRAFT->name => new ApprovalFlowStep(
                permission: null, // No permission required for draft
                next: self::PENDING->name,
            ),
            self::PENDING->name => new ApprovalFlowStep(
                permission: 'approvePending',
                next: self::APPROVED->name,
            ),
        ];
    }

    public static function getRejectionStatuses(): array
    {
        return [
            self::PENDING->value => self::REJECTED->value,
        ];
    }

    public static function getCompletedStatus(): string
    {
        return self::APPROVED->value;
    }

    public static function getStatusTransitions(): array
    {
        return [];
    }
}
