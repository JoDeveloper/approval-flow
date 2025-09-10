<?php

namespace Jodeveloper\ApprovalFlow;

use Jodeveloper\ApprovalFlow\Exceptions\ApprovalFlowException;

class ApprovalFlowManager
{
    /**
     * Get approval statistics for a model
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function getApprovalStats($model): array
    {
        if (! method_exists($model, 'approvalHistory')) {
            throw ApprovalFlowException::missingStatusEnum(get_class($model));
        }

        /** @phpstan-ignore-next-line */
        $history = $model->approvalHistory;

        return [
            'total_approvals' => $history->where('action', 'approved')->count(),
            'total_rejections' => $history->where('action', 'rejected')->count(),
            /** @phpstan-ignore-next-line */
            'current_status' => $model->status?->code,
            /** @phpstan-ignore-next-line */
            'is_completed' => $model->isCompleted(),
            /** @phpstan-ignore-next-line */
            'can_approve' => $model->canApprove(),
            /** @phpstan-ignore-next-line */
            'can_reject' => $model->canReject(),
            /** @phpstan-ignore-next-line */
            'next_step' => $model->getCurrentApprovalStep()?->permission,
        ];
    }

    /**
     * Bulk approve multiple models
     *
     * @param  iterable<\Illuminate\Database\Eloquent\Model>  $models
     */
    public function bulkApprove(iterable $models, ?string $comment = null): array
    {
        $results = ['success' => [], 'failed' => []];

        foreach ($models as $model) {
            try {
                /** @phpstan-ignore-next-line */
                if ($model->approve($comment)) {
                    /** @phpstan-ignore-next-line */
                    $results['success'][] = $model->id;
                } else {
                    /** @phpstan-ignore-next-line */
                    $results['failed'][] = ['id' => $model->id, 'reason' => 'Cannot approve'];
                }
            } catch (\Exception $e) {
                /** @phpstan-ignore-next-line */
                $results['failed'][] = ['id' => $model->id, 'reason' => $e->getMessage()];
            }
        }

        return $results;
    }
}
