<?php

namespace Jodeveloper\ApprovalFlow\Traits;

use Jodeveloper\ApprovalFlow\Events\ModelApproved;
use Jodeveloper\ApprovalFlow\Events\ModelRejected;
use Jodeveloper\ApprovalFlow\Exceptions\ApprovalFlowException;
use Jodeveloper\ApprovalFlow\DataTransferObjects\ApprovalFlowStep;

trait HasApprovalFlow
{
    /**
     * Get the next approval status for the current user
     */
    public static function getNextApprovalStatus($model): int
    {
        $user = auth()->user();
        $currentStatus = $model->status->code;

        $statusEnum = static::getStatusEnum();
        $approvalFlow = $statusEnum::getApprovalFlow();
        $currentFlowStep = $approvalFlow[$currentStatus] ?? null;

        if ($currentFlowStep && $user->can($currentFlowStep->permission, $model)) {
            return static::getStatusId($currentFlowStep->next);
        }

        return static::getStatusId($currentStatus);
    }

    /**
     * Get the rejection status for current approval step
     */
    public static function getRejectStatus($model): ?int
    {
        $currentStatus = $model->status->code;
        $statusEnum = static::getStatusEnum();
        $rejectionStatuses = $statusEnum::getRejectionStatuses();

        if (isset($rejectionStatuses[$currentStatus])) {
            return static::getStatusId($rejectionStatuses[$currentStatus]);
        }

        return null;
    }

    /**
     * Approve the current step
     */
    public function approve(?string $comment = null): bool
    {
        if (!$this->canApprove()) {
            throw ApprovalFlowException::unauthorizedAction('approve', static::class);
        }

        $previousStatus = $this->status->code;
        $nextStatusId = static::getNextApprovalStatus($this);

        if ($nextStatusId !== static::getStatusId($this->status->code)) {
            $updateData = ['status_id' => $nextStatusId];

            if ($comment && $this->hasFillableAttribute('approval_comment')) {
                $updateData['approval_comment'] = $comment;
            }

            $this->update($updateData);

            event(new ModelApproved($this, $previousStatus, $this->fresh()->status->code, $comment));

            return true;
        }

        return false;
    }

    /**
     * Reject the current step with optional note
     */
    public function reject(?string $note = null): bool
    {
        if (!$this->canReject()) {
            throw ApprovalFlowException::unauthorizedAction('reject', static::class);
        }

        $previousStatus = $this->status->code;
        $rejectStatusId = static::getRejectStatus($this);

        if ($rejectStatusId) {
            $updateData = ['status_id' => $rejectStatusId];

            if ($note && $this->hasFillableAttribute('rejection_note')) {
                $updateData['rejection_note'] = $note;
            }

            $this->update($updateData);

            event(new ModelRejected($this, $previousStatus, $this->fresh()->status->code, $note));

            return true;
        }

        return false;
    }

    /**
     * Check if current user can approve this step
     */
    public function canApprove(): bool
    {
        $user = auth()->user();
        $currentFlowStep = $this->getCurrentApprovalStep();

        return $currentFlowStep && $user && $user->can($currentFlowStep->permission, $this);
    }

    /**
     * Check if current user can reject this step
     */
    public function canReject(): bool
    {
        return $this->canApprove();
    }

    /**
     * Get the next status code without checking permissions
     */
    public function getNextStatusCode(): ?string
    {
        $currentStatus = $this->status->code;
        $statusEnum = static::getStatusEnum();

        $statusTransitions = $statusEnum::getStatusTransitions() ?? [];
        if (isset($statusTransitions[$currentStatus])) {
            return $statusTransitions[$currentStatus];
        }

        $approvalFlow = $statusEnum::getApprovalFlow();
        if (isset($approvalFlow[$currentStatus])) {
            return $approvalFlow[$currentStatus]->next;
        }

        return null;
    }

    /**
     * Get current approval step info
     */
    public function getCurrentApprovalStep(): ?ApprovalFlowStep
    {
        $currentStatus = $this->status->code;
        $statusEnum = static::getStatusEnum();
        $approvalFlow = $statusEnum::getApprovalFlow();

        return $approvalFlow[$currentStatus] ?? null;
    }

    /**
     * Check if the model is in approval process
     */
    public function isInApprovalProcess(): bool
    {
        return $this->getCurrentApprovalStep() !== null;
    }

    /**
     * Check if the model is completed
     */
    public function isCompleted(): bool
    {
        $statusEnum = static::getStatusEnum();
        return $this->status->code === $statusEnum::getCompletedStatus();
    }

    /**
     * Get approval history
     */
    public function approvalHistory()
    {
        if (config('approval-flow.log_approvals', true)) {
            return $this->morphMany(
                config('approval-flow.models.approval_log'),
                'approvable'
            )->latest();
        }

        return collect([]);
    }

    /**
     * Get status ID from enum or string
     */
    public static function getStatusId($status): int
    {
        $code = is_string($status) ? $status : $status->name;
        return static::statuses($code)->id;
    }

    /**
     * Get the status enum class - must be implemented by the model
     */
    abstract public static function getStatusEnum(): string;

    /**
     * Get statuses - should be implemented by the model or use existing method
     */
    abstract public static function statuses(string $code);

    /**
     * Check if attribute is fillable
     */
    protected function hasFillableAttribute(string $attribute): bool
    {
        return in_array($attribute, $this->getFillable());
    }
}
