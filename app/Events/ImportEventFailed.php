<?php
namespace App\Events;

use App\Models\ImportedEvent;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class ImportEventFailed
{
    use Dispatchable, SerializesModels;

    public $event;

    public function __construct(ImportedEvent $event)
    {
        $this->event = $event;
    }
}