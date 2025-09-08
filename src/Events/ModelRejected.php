<?php

namespace Jodeveloper\ApprovalFlow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ModelRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public $model,
        public string $previousStatus,
        public string $rejectedStatus,
        public ?string $note = null,
        public ?int $userId = null
    ) {
        $this->userId = $userId ?? auth()->id();
    }
}
