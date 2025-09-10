<?php

namespace Jodeveloper\ApprovalFlow\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Jodeveloper\ApprovalFlow\Tests\Enums\TestStatus;
use Jodeveloper\ApprovalFlow\Traits\HasApprovalFlow;

class TestModel extends Model
{
    use HasApprovalFlow;

    protected $fillable = [];

    protected $guarded = [];

    public static function getStatusEnum(): string
    {
        return TestStatus::class;
    }

    public static function getStatus(string $code): object
    {
        $status = TestStatus::tryFrom($code) ?? TestStatus::DRAFT;

        $ids = [
            'DRAFT' => 1,
            'PENDING' => 2,
            'APPROVED' => 3,
            'REJECTED' => 4,
        ];

        return (object) ['id' => $ids[$status->value] ?? 1, 'name' => $status->name];
    }

    public function setFillable(array $fillable)
    {
        $this->fillable = $fillable;
    }

    public function setGuarded(array $guarded)
    {
        $this->guarded = $guarded;
    }
}
