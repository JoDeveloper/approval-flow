<?php

namespace Jodeveloper\ApprovalFlow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ModelApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public $model,
        public string $previousStatus,
        public string $newStatus,
        public ?string $comment = null,
        public ?int $userId = null
    ) {
        $this->userId = $userId ?? (auth()->check() ? auth()->id() : null);
    }
}
