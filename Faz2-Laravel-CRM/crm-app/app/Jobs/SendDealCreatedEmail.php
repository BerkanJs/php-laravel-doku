<?php

namespace App\Jobs;

use App\Models\Deal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendDealCreatedEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Deal $deal)   // constructor property promotion (Gun 6) - deal, jobs tablosunda serialize edilip saklanir
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // gercek bir Mail::send() yerine sahte, log'a yazan bir "e-posta" - queue mekanizmasini gostermek amacli
        Log::info("E-posta gonderildi (sahte): Deal #{$this->deal->id} icin '{$this->deal->title}'");
    }
}
