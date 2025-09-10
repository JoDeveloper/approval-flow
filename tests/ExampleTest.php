<?php

use Jodeveloper\ApprovalFlow\Tests\Models\TestModel;

it('can test', function () {
    expect(true)->toBeTrue();
});

it('handles null status in getNextApprovalStatus', function () {
    $model = new TestModel;
    $model->status = null;

    $result = TestModel::getNextApprovalStatus($model);

    expect($result)->toBeString();
});

it('handles null status in canApprove', function () {
    $model = new TestModel;
    $model->status = null;

    $result = $model->canApprove();

    expect($result)->toBeFalse();
});

it('handles null status in getCurrentApprovalStep', function () {
    $model = new TestModel;
    $model->status = null;

    $result = $model->getCurrentApprovalStep();

    expect($result)->toBeNull();
});

it('handles null status in isCompleted', function () {
    $model = new TestModel;
    $model->status = null;

    $result = $model->isCompleted();

    expect($result)->toBeFalse();
});

it('handles string status in getStatusId', function () {
    // Test with string
    $result = TestModel::getStatusId('DRAFT');
    expect($result)->toBeInt();
});

it('throws exception for invalid status type in getStatusId', function () {
    $this->expectException(\InvalidArgumentException::class);
    TestModel::getStatusId(123);
});

it('handles fillable attributes correctly', function () {
    $model = new TestModel;
    $model->setFillable(['approval_comment']);

    $result = $model->hasFillableAttribute('approval_comment');
    expect($result)->toBeTrue();

    $result = $model->hasFillableAttribute('non_existent');
    expect($result)->toBeFalse();
});

it('handles guarded attributes correctly', function () {
    $model = new TestModel;
    $model->setFillable([]); // Empty fillable
    $model->setGuarded(['id']); // Guarded id

    $result = $model->hasFillableAttribute('name');
    expect($result)->toBeTrue(); // Should be fillable since not guarded

    $result = $model->hasFillableAttribute('id');
    expect($result)->toBeFalse(); // Should not be fillable since guarded
});

it('handles all guarded correctly', function () {
    $model = new TestModel;
    $model->setFillable([]);
    $model->setGuarded(['*']); // All guarded

    $result = $model->hasFillableAttribute('name');
    expect($result)->toBeFalse();
});

it('handles approval and rejection with null status', function () {
    $model = new TestModel;
    $model->status = null;

    // Should throw unauthorized exception
    expect(fn () => $model->approve())->toThrow(\Jodeveloper\ApprovalFlow\Exceptions\ApprovalFlowException::class);
    expect(fn () => $model->reject())->toThrow(\Jodeveloper\ApprovalFlow\Exceptions\ApprovalFlowException::class);
});
