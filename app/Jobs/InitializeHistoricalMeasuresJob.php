<?php

namespace App\Jobs;

use App\Models\Zone;
use App\Models\Field;
use Illuminate\Bus\Queueable;
use App\Services\WiseconnService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class InitializeHistoricalMeasuresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $field;
    protected $zone;

    public function __construct(Field $field, Zone $zone)
    {
        $this->field = $field;
        $this->zone = $zone;
    }

    public function handle()
    {
        $wiseconnService = new WiseconnService();
        $wiseconnService->initializeHistoricalMeasures($this->field, $this->zone);
    }

}
