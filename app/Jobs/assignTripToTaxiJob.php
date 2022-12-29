<?php

namespace App\Jobs;

use App\Http\Controllers\TripController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class assignTripToTaxiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $location_client;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($location_client)
    {
        $this->location_client=$location_client;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $trip=new TripController();
        $city=$this->getCityLocatedClient($this->location_client);
        if(!$city){
            return ['status'=>false,'message'=>'Lo sentimos, te encuentras fuera de nuestra área de servicio.'];
        }
    }
}
