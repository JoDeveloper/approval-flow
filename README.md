# Nakhlah Approval Flow

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jodeveloper/nakhlah-approval-flow.svg?style=flat-square)](https://packagist.org/packages/jodeveloper/nakhlah-approval-flow)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jodeveloper/nakhlah-approval-flow/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/jodeveloper/nakhlah-approval-flow/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/jodeveloper/nakhlah-approval-flow/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/jodeveloper/nakhlah-approval-flow/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/jodeveloper/nakhlah-approval-flow.svg?style=flat-square)](https://packagist.org/packages/jodeveloper/nakhlah-approval-flow)

A powerful Laravel package for managing approval workflows using PHP 8.1+ enums and traits. Create complex approval processes with ease!

## Features

- ✅ **Type-safe approval flows** using PHP 8.1 enums
- ✅ **Reusable trait** for any Eloquent model
- ✅ **Permission-based approvals** with Laravel's authorization system
- ✅ **Event-driven architecture** for notifications and logging
- ✅ **Automatic activity logging** with user tracking
- ✅ **Bulk approval operations**
- ✅ **Artisan command** to generate approval flow enums
- ✅ **Comprehensive testing** with Pest

## Installation

You can install the package via composer:

```bash
composer require jodeveloper/nakhlah-approval-flow
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="nakhlah-approval-flow-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="nakhlah-approval-flow-config"
```

## Quick Start

### 1. Create an Approval Flow Enum

Generate a new approval flow enum:

```bash
php artisan make:approval-flow Document
```

This creates `app/Enums/DocumentStatuses.php`:

```php
<?php

namespace App\Enums;

use jodeveloper\ApprovalFlow\Contracts\ApprovalStatusInterface;
use jodeveloper\ApprovalFlow\DataTransferObjects\ApprovalFlowStep;

enum DocumentStatuses: string implements ApprovalStatusInterface
{
    case DRAFT = 'DRAFT';
    case MANAGER_REVIEW = 'MANAGER_REVIEW';
    case DIRECTOR_REVIEW = 'DIRECTOR_REVIEW';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';

    public static function getApprovalFlow(): array
    {
        return [
            self::DRAFT->name => new ApprovalFlowStep(
                permission: null, // No permission required for initial submission
                next: self::MANAGER_REVIEW->name,
            ),
            self::MANAGER_REVIEW->name => new ApprovalFlowStep(
                permission: 'managerApprove',
                next: self::DIRECTOR_REVIEW->name,
            ),
            self::DIRECTOR_REVIEW->name => new ApprovalFlowStep(
                permission: 'directorApprove',
                next: self::APPROVED->name,
            ),
        ];
    }

    public static function getRejectionStatuses(): array
    {
        return [
            self::MANAGER_REVIEW->value => self::REJECTED->value,
            self::DIRECTOR_REVIEW->value => self::REJECTED->value,
        ];
    }

    public static function getCompletedStatus(): string
    {
        return self::APPROVED->value;
    }

    /**
     * Define simple status transitions that bypass the approval workflow.
     * Use this for automatic transitions that don't require permissions.
     *
     * @return array<string, string> Maps current status to next status
     */
    public static function getStatusTransitions(): array
    {
        return [
            // Example: 'AUTO_APPROVED' => 'COMPLETED',
            // Example: 'EXPIRED' => 'CANCELLED',
        ];
    }
}
```

### 2. Add the Trait to Your Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use jodeveloper\ApprovalFlow\Traits\HasApprovalFlow;

class Document extends Model
{
    use HasApprovalFlow;

    protected $fillable = [
        'title',
        'content',
        'status_id',
        'approval_comment',
        'rejection_note',
    ];

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public static function getStatusEnum(): string
    {
        return DocumentStatuses::class;
    }

    public static function getStatus(string $code)
    {
        return Status::where('code', $code)->first();
    }
}
```

### 3. Use in Controllers

```php
<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class DocumentApprovalController extends Controller
{
    public function approve(Request $request, Document $document)
    {
        if (!$document->canApprove()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($document->approve($request->comment)) {
            return response()->json([
                'message' => 'Document approved successfully',
                'status' => $document->fresh()->status->name
            ]);
        }

        return response()->json(['error' => 'Could not approve document'], 400);
    }

    public function reject(Request $request, Document $document)
    {
        $request->validate(['note' => 'required|string|max:1000']);

        if (!$document->canReject()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($document->reject($request->note)) {
            return response()->json(['message' => 'Document rejected']);
        }

        return response()->json(['error' => 'Could not reject document'], 400);
    }
}
```

## Usage Examples

### Basic Approval Operations

```php
$document = Document::find(1);

// Check permissions
if ($document->canApprove()) {
    $document->approve('Looks good!');
}

if ($document->canReject()) {
    $document->reject('Please revise section 3');
}

// Check status
if ($document->isCompleted()) {
    // Document is fully approved
}

if ($document->isInApprovalProcess()) {
    $step = $document->getCurrentApprovalStep();
    echo "Waiting for: " . $step->role;
}
```

### Bulk Operations

```php
use jodeveloper\ApprovalFlow\ApprovalFlowManager;

$manager = app(ApprovalFlowManager::class);

// Bulk approve multiple documents
$documents = Document::where('status_id', $pendingStatusId)->get();
$results = $manager->bulkApprove($documents, 'Batch approval');

// Results contain success and failed arrays
echo "Approved: " . count($results['success']);
echo "Failed: " . count($results['failed']);
```

### Approval Statistics

```php
$manager = app(ApprovalFlowManager::class);
$stats = $manager->getApprovalStats($document);

/*
Array output:
[
    'total_approvals' => 2,
    'total_rejections' => 1,
    'current_status' => 'MANAGER_REVIEW',
    'is_completed' => false,
    'can_approve' => true,
    'can_reject' => true,
    'next_step' => 'Manager'
]
*/
```

### Event Handling

The package fires events that you can listen to:

```php
// In your EventServiceProvider
use jodeveloper\ApprovalFlow\Events\ModelApproved;
use jodeveloper\ApprovalFlow\Events\ModelRejected;

protected $listen = [
    ModelApproved::class => [
        SendApprovalNotification::class,
    ],
    ModelRejected::class => [
        SendRejectionNotification::class,
    ],
];
```

Create listeners:

```php
<?php

namespace App\Listeners;

use jodeveloper\ApprovalFlow\Events\ModelApproved;
use Illuminate\Support\Facades\Mail;

class SendApprovalNotification
{
    public function handle(ModelApproved $event): void
    {
        // Send notification to next approver or completion notification
        $model = $event->model;
        $nextStep = $model->getCurrentApprovalStep();
        
        if ($nextStep) {
            // Notify next approver
            Mail::to($nextStep->role)->send(new ApprovalNeeded($model));
        } else {
            // Notify completion
            Mail::to($model->user)->send(new ApprovalCompleted($model));
        }
    }
}
```

### Approval History

```php
// Get approval history for a model
$history = $document->approvalHistory;

foreach ($history as $log) {
    echo "{$log->user->name} {$log->action} on {$log->created_at}";
    if ($log->comment) {
        echo " - Comment: {$log->comment}";
    }
}
```

### Custom Status Transitions

For **simple status changes** that don't require approval workflow:

```php
enum DocumentStatuses: string implements ApprovalStatusInterface
{
    case DRAFT = 'DRAFT';
    case ON_HOLD = 'ON_HOLD';
    case ARCHIVED = 'ARCHIVED';
    case AUTO_APPROVED = 'AUTO_APPROVED';
    case EXPIRED = 'EXPIRED';
    // ... other cases

    public static function getStatusTransitions(): array
    {
        return [
            // Simple transitions without permissions
            self::DRAFT->name => self::ON_HOLD->name,
            self::ON_HOLD->name => self::DRAFT->name,

            // Automatic system transitions
            self::AUTO_APPROVED->name => self::APPROVED->name,
            self::EXPIRED->name => self::ARCHIVED->name,
        ];
    }

    // ... other methods
}
```

**When to use `getStatusTransitions()`:**
- ✅ **Automatic transitions** (system-triggered)
- ✅ **Simple state changes** (no approval needed)
- ✅ **Performance optimization** (bypass permission checks)
- ✅ **Fallback transitions** (when approval flow not applicable)

**When to use `getApprovalFlow()`:**
- ❌ **Permission-based approvals**
- ❌ **Multi-step workflows**
- ❌ **User-triggered transitions**
- ❌ **Audit trails required**

## Advanced Configuration

### Custom Approval Log Model

If you want to extend the approval logging functionality:

```php
<?php

namespace App\Models;

use jodeveloper\ApprovalFlow\Models\ApprovalLog as BaseApprovalLog;

class CustomApprovalLog extends BaseApprovalLog
{
    protected $fillable = [
        ...parent::$fillable,
        'department',
        'priority',
    ];

    // Add custom relationships or methods
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
```

Then update your config:

```php
// config/approval-flow.php
return [
    'models' => [
        'approval_log' => \App\Models\CustomApprovalLog::class,
    ],
    // ...
];
```

### Disable Logging

```php
// In your .env file
APPROVAL_FLOW_LOG_ENABLED=false

// Or in config/approval-flow.php
'log_approvals' => false,
```

## Testing

```bash
composer test
```

### Example Test

```php
<?php

use App\Models\Document;
use jodeveloper\ApprovalFlow\Events\ModelApproved;

it('can approve a document', function () {
    Event::fake();
    
    $user = User::factory()->create();
    $this->actingAs($user);
    
    // Give user permission
    Gate::define('documents.manager-approve', fn() => true);
    
    $document = Document::factory()->create([
        'status_id' => Status::where('code', 'MANAGER_REVIEW')->first()->id
    ]);
    
    expect($document->canApprove())->toBeTrue();
    expect($document->approve('Approved!'))->toBeTrue();
    
    Event::assertDispatched(ModelApproved::class);
    
    $document->refresh();
    expect($document->status->code)->toBe('DIRECTOR_REVIEW');
});

it('cannot approve without permission', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    
    // No permission granted
    Gate::define('documents.manager-approve', fn() => false);
    
    $document = Document::factory()->create([
        'status_id' => Status::where('code', 'MANAGER_REVIEW')->first()->id
    ]);
    
    expect($document->canApprove())->toBeFalse();
    expect(fn() => $document->approve())->toThrow(ApprovalFlowException::class);
});
```

## Package Structure

```
src/
├── ApprovalFlowServiceProvider.php
├── ApprovalFlowManager.php
├── Commands/
│   └── MakeApprovalFlowCommand.php
├── Contracts/
│   └── ApprovalStatusInterface.php
├── DataTransferObjects/
│   └── ApprovalFlowStep.php
├── Events/
│   ├── ModelApproved.php
│   └── ModelRejected.php
├── Exceptions/
│   └── ApprovalFlowException.php
├── Listeners/
│   └── LogApprovalActivity.php
├── Models/
│   └── ApprovalLog.php
└── Traits/
    └── HasApprovalFlow.php
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [jodeveloper](https://github.com/jodeveloper)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
