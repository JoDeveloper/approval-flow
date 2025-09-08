<?php

namespace Jodeveloper\ApprovalFlow\Listeners;

use Jodeveloper\ApprovalFlow\Events\ModelApproved;
use Jodeveloper\ApprovalFlow\Events\ModelRejected;

class LogApprovalActivity
{
    public function handle(ModelApproved|ModelRejected $event): void
    {
        if (!config('approval-flow.log_approvals', true)) {
            return;
        }

        $logModel = config('approval-flow.models.approval_log');

        $logModel::create([
            'approvable_type' => get_class($event->model),
            'approvable_id' => $event->model->id,
            'user_id' => $event->userId,
            'action' => $event instanceof ModelApproved ? 'approved' : 'rejected',
            'previous_status' => $event->previousStatus,
            'new_status' => $event instanceof ModelApproved ? $event->newStatus : $event->rejectedStatus,
            'comment' => $event->comment ?? $event->note ?? null,
            'metadata' => [
                'user_agent' => request()?->header('User-Agent'),
                'ip_address' => request()?->ip(),
                'timestamp' => now(),
            ],
        ]);
    }
}
