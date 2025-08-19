<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Realiza un backup completo de la base de datos y archivos de la aplicación.';

    /**
     * The process timeout.
     *
     * @var int
     */
    protected $timeout = 600;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando el backup...');
        $disk = 'local';
        $destinationPath = storage_path('app/backups/');

        // 1. Preparar el directorio de destino
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        // 2. Realizar el backup de la base de datos
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');

        $backupFile = $destinationPath . 'db-backup-' . date('Y-m-d-H-i-s') . '.sql';
        $command = "mysqldump --user={$dbUser} --password={$dbPass} --host={$dbHost} {$dbName} > {$backupFile}";

        $this->info("Realizando backup de la base de datos: {$dbName}...");
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->error('¡El backup de la base de datos falló! Error: ' . implode("\n", $output));
            return Command::FAILURE;
        }

        // 3. Comprimir la base de datos y los archivos
        $zipFile = $destinationPath . 'full-backup-' . date('Y-m-d-H-i-s') . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($zipFile, \ZipArchive::CREATE) === true) {
            // Añadir el archivo de la base de datos
            $zip->addFile($backupFile, basename($backupFile));

            // Añadir el directorio de almacenamiento
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(storage_path('app')),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = 'storage/' . substr($filePath, strlen(storage_path('app')));
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();
            $this->info("Backup completo creado en: {$zipFile}");

            // Eliminar el archivo .sql sin comprimir
            File::delete($backupFile);
        } else {
            $this->error('¡No se pudo crear el archivo zip!');
            return Command::FAILURE;
        }

        // 4. Limpiar los backups antiguos (eliminar backups de más de 7 días)
        $this->info('Limpiando backups antiguos...');
        $files = File::files($destinationPath);
        foreach ($files as $file) {
            if ($file->getMTime() < (time() - 60 * 60 * 24 * 7)) {
                File::delete($file);
            }
        }
        $this->info('Backup completado con éxito.');

        return Command::SUCCESS;
    }
}