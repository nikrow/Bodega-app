<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\FileUploadController;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;

class CustomLivewireController extends FileUploadController
{
    public function handle()
    {
        try {
            $disk = FileUploadConfiguration::disk();
            $filePaths = $this->validateAndStore(request('files'), $disk);
            return ['paths' => $filePaths];
        } catch (\Exception $e) {
            Log::error('Error en la carga de archivos: ' . $e->getMessage());
            return response()->json(['error' => 'Error al procesar la carga de archivos'], 500);
        }
    }
}
