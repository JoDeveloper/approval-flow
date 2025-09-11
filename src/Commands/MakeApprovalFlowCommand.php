<?php

namespace Jodeveloper\ApprovalFlow\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeApprovalFlowCommand extends Command
{
    protected $signature = 'make:approval-flow {name : The name of the approval flow enum}';

    protected $description = 'Create a new approval flow enum';

    public function handle(Filesystem $files): int
    {
        $name = $this->argument('name');
        $enumName = Str::studly($name).'Statuses';
        $path = app_path("Enums/{$enumName}.php");

        if ($files->exists($path)) {
            $this->error("Enum {$enumName} already exists!");

            return self::FAILURE;
        }

        $files->ensureDirectoryExists(dirname($path));

        $stub = $this->getStub();
        $stub = str_replace(['{{enumName}}', '{{namespace}}'], [$enumName, 'App\\Enums'], $stub);

        $files->put($path, $stub);

        $this->info("Approval flow enum created successfully at {$path}");

        return self::SUCCESS;
    }

    protected function getStub(): string
    {
        return <<<'EOT'
            <?php

            namespace {{namespace}};

            use Jodeveloper\ApprovalFlow\Contracts\ApprovalStatusInterface;
            use Jodeveloper\ApprovalFlow\DataTransferObjects\ApprovalFlowStep;

            enum {{enumName}}: string implements ApprovalStatusInterface
            {
                case DRAFT = 'DRAFT';
                case PENDING_APPROVAL = 'PENDING_APPROVAL';
                case APPROVED = 'APPROVED';
                case REJECTED = 'REJECTED';

                public static function getApprovalFlow(): array
                {
                    return [
                        self::DRAFT->name => new ApprovalFlowStep(
                            permission: null, // No permission required
                            next: self::PENDING_APPROVAL->name,
                        ),
                        self::PENDING_APPROVAL->name => new ApprovalFlowStep(
                            permission: 'approve',
                            next: self::APPROVED->name,
                        ),
                    ];
                }

                public static function getRejectionStatuses(): array
                {
                    return [
                        self::PENDING_APPROVAL->name => self::REJECTED->name,
                    ];
                }

                public static function getCompletedStatus(): string
                {
                    return self::APPROVED->name;
                }

                public static function getStatusTransitions(): array
                {
                    return [
                        // Add custom transitions here if needed
                    ];
                }
            }
            EOT;
    }
}
