<?php

namespace App\Console\Commands;

use App\Models\Piece;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class archivePassedPieces extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:archive-passed-pieces';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pieces = Piece::where('expired_date', '<', Carbon::today())->where('archive', false)->get();

        foreach($pieces as $piece)
            $piece->archive();
    }
}
