<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoogleGeoLocationController extends Controller
{
    /**
     * Returns the city in which point is located
     *
     * @param array $position
     */
    public function getCityLocated($lat=-1.635153,$lng=-79.006379){
        $key=env('APP_GOOGLE_KEY');
        $url="https://maps.googleapis.com/maps/api/geocode/json?latlng=$lat, $lng&key=$key";
        $data= Http::withHeaders([
            'Content-Type'=>'application/json',
        ])->post($url)->json();

        if($data['status']=='OK'){
           $city= $data['results'][2]['address_components'][2]['long_name'];
            return $city;
        }
        return false;
    }

    /**
     * Returns the distance of the vehicle between two points
     *
     * @param array $origin
     * @param array $destination
     * @return mixed
     */
    public static function distanceMatrix(array $origin, array $destination): mixed
    {
        $key=env('APP_GOOGLE_KEY');
        $url="https://maps.googleapis.com/maps/api/distancematrix/json?destinations=$destination[0],$destination[1]&origins=$origin[0],$origin[1]&key=$key";
        return Http::withHeaders([
            'Content-Type'=>'application/json',
        ])->post($url)->json();
    }



}
