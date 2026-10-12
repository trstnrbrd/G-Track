<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Support\DatabaseBackup;
use App\Support\ShopSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'gtrack:backup
                            {--keep=14 : How many of the newest backups to keep on this PC}';

    protected $description = 'Save a SQL backup of the shop data to storage/app/private/backups';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $name = DatabaseBackup::filename();
        $path = "backups/{$name}";

        $disk->makeDirectory('backups');

        $handle = fopen($disk->path($path), 'wb');

        if ($handle === false) {
            $this->error('Could not write '.$path);

            return self::FAILURE;
        }

        $rows = DatabaseBackup::stream(function (string $chunk) use ($handle) {
            fwrite($handle, $chunk);
        });

        fclose($handle);

        Setting::put(ShopSettings::LAST_BACKUP, [
            'at' => now()->toIso8601String(),
            'via' => 'scheduled',
            'rows' => $rows,
            'file' => $name,
        ], null);

        // Old ones go, newest stay. Names sort by date, so reverse-sorted is newest first.
        $files = collect($disk->files('backups'))->filter(fn ($f) => str_ends_with($f, '.sql'))->sortDesc()->values();
        $keep = max(1, (int) $this->option('keep'));

        $files->slice($keep)->each(fn ($old) => $disk->delete($old));

        $this->info("Backed up {$rows} rows to {$path} (keeping the newest {$keep}).");

        return self::SUCCESS;
    }
}
