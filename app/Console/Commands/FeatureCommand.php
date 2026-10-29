<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Pennant\Feature;

class FeatureCommand extends Command
{
    protected $signature = 'ezfin:feature {name? : Tên feature flag} {state? : on hoặc off}';

    protected $description = 'Xem hoặc bật/tắt feature flag của các module';

    public function handle(): int
    {
        $defined = array_keys(config('ezfin.features', []));
        $name = $this->argument('name');
        $state = $this->argument('state');

        if ($name === null) {
            $this->table(['Feature', 'Trạng thái'], array_map(
                fn (string $feature) => [$feature, Feature::active($feature) ? 'bật' : 'tắt'],
                $defined,
            ));

            return self::SUCCESS;
        }

        if (! in_array($name, $defined, true)) {
            $this->error("Không có feature [$name]. Các feature: ".implode(', ', $defined));

            return self::FAILURE;
        }

        if ($state === null) {
            $this->line("$name: ".(Feature::active($name) ? 'bật' : 'tắt'));

            return self::SUCCESS;
        }

        if (! in_array($state, ['on', 'off'], true)) {
            $this->error('Trạng thái phải là on hoặc off.');

            return self::INVALID;
        }

        $state === 'on' ? Feature::activate($name) : Feature::deactivate($name);
        $this->info('Đã '.($state === 'on' ? 'bật' : 'tắt')." [$name].");

        return self::SUCCESS;
    }
}
