<?php

namespace Jodeveloper\ApprovalFlow;

use Jodeveloper\ApprovalFlow\Commands\MakeApprovalFlowCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ApprovalFlowServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('nakhlah-approval-flow')
            ->hasConfigFile()
            ->hasMigrations([
                'create_approval_logs_table',
            ])
            ->hasCommand(MakeApprovalFlowCommand::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->copyAndRegisterServiceProviderInApp();
            });
    }

    public function packageRegistered(): void
    {
        $this->app->bind('approval-flow', function () {
            return new ApprovalFlowManager;
        });
    }

    public function packageBooted(): void
    {
        // Register event listeners if needed
        if (config('approval-flow.log_approvals', true)) {
            $this->app['events']->listen(
                Events\ModelApproved::class,
                Listeners\LogApprovalActivity::class
            );

            $this->app['events']->listen(
                Events\ModelRejected::class,
                Listeners\LogApprovalActivity::class
            );
        }
    }
}
