<?php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportCompletedEvent
{
    use Dispatchable, SerializesModels;

    public $summary;
    public $rowDetails;

    public function __construct($summary, $rowDetails)
    {
        $this->summary = $summary;
        $this->rowDetails = $rowDetails;
    }
}