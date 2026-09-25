<?php

namespace TDSoft\AiTutor\Licensing;

use Illuminate\Console\Command;

final class LicenseCommand extends Command
{
    protected $signature = 'ai-tutor:license {action=status : status, init or refresh} {--force : Refresh before the next due time}';

    protected $description = 'Show/init installation identity or refresh an already activated license';

    public function handle(InstallationIdentity $identity, LicenseReader $reader, LicenseClient $client): int
    {
        try {
            $mode = new LicenseMode;
            if ($mode->value() === 'source_owned') {
                if ($this->argument('action') !== 'status') {
                    throw new LicenseException('LICENSE_NOT_REQUIRED');
                }
                $this->line('AI Tutor license: source_owned');
                $this->line('Modules: '.implode(', ', $mode->modules()));

                return self::SUCCESS;
            }
            switch ($this->argument('action')) {
                case 'init':
                    $state = $identity->initialize();
                    $this->line('Installation: '.$state->installation_id);
                    $this->line('Domain: '.$identity->domain());

                    return self::SUCCESS;
                case 'refresh':
                    $client->refresh((bool) $this->option('force'));
                    break;
                case 'status':
                    break;
                default:
                    $this->error('Expected status, init or refresh.');

                    return self::FAILURE;
            }
            $status = $reader->status();
            $this->line('AI Tutor license: '.$status->state);
            if ($status->errorCode) {
                $this->line($status->errorCode);
            }

            return $status->usable() ? self::SUCCESS : self::FAILURE;
        } catch (LicenseException $error) {
            $this->error($error->errorCode);

            return self::FAILURE;
        }
    }
}
