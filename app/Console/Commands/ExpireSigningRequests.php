<?php

namespace App\Console\Commands;

use App\Models\DocumentSigningRequest;
use Illuminate\Console\Command;

class ExpireSigningRequests extends Command
{
    protected $signature = 'signing:expire-requests';

    protected $description = 'Mark pending signing requests past their expiry date as expired';

    public function handle(): void
    {
        $count = DocumentSigningRequest::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Marked {$count} signing request(s) as expired.");
    }
}
