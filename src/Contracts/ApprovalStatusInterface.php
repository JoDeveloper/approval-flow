<?php

namespace Jodeveloper\ApprovalFlow\Contracts;

use Jodeveloper\ApprovalFlow\DataTransferObjects\ApprovalFlowStep;

interface ApprovalStatusInterface
{
    /**
     * Get the approval flow configuration
     *
     * @return array<string, ApprovalFlowStep>
     */
    public static function getApprovalFlow(): array;

    /**
     * Get mapping of approval statuses to their rejection counterparts
     *
     * @return array<string, string>
     */
    public static function getRejectionStatuses(): array;

    /**
     * Get the final completed status
     */
    public static function getCompletedStatus(): string;

    /**
     * Optional: Get custom status transitions (non-approval related)
     *
     * @return array<string, string>
     */
    public static function getStatusTransitions(): array;
}
