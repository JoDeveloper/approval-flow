<?php

namespace Jodeveloper\ApprovalFlow;

use Jodeveloper\ApprovalFlow\Exceptions\ApprovalFlowException;

class ApprovalFlowManager
{
    /**
     * Get approval statistics for a model
     */
    public function getApprovalStats($model): array
    {
        if (! method_exists($model, 'approvalHistory')) {
            throw ApprovalFlowException::missingStatusEnum(get_class($model));
        }

        $history = $model->approvalHistory;

        return [
            'total_approvals' => $history->where('action', 'approved')->count(),
            'total_rejections' => $history->where('action', 'rejected')->count(),
            'current_status' => $model->status?->code,
            'is_completed' => $model->isCompleted(),
            'can_approve' => $model->canApprove(),
            'can_reject' => $model->canReject(),
            'next_step' => $model->getCurrentApprovalStep()?->permission,
        ];
    }

    /**
     * Bulk approve multiple models
     */
    public function bulkApprove(iterable $models, ?string $comment = null): array
    {
        $results = ['success' => [], 'failed' => []];

        foreach ($models as $model) {
            try {
                if ($model->approve($comment)) {
                    $results['success'][] = $model->id;
                } else {
                    $results['failed'][] = ['id' => $model->id, 'reason' => 'Cannot approve'];
                }
            } catch (\Exception $e) {
                $results['failed'][] = ['id' => $model->id, 'reason' => $e->getMessage()];
            }
        }

        return $results;
    }
}
