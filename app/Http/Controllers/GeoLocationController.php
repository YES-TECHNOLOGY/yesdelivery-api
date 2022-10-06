<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GeoLocationController extends Controller
{
    /**
     * Returns true or false if a point is within the specified radius with method haversine.
     *
     * @param array $origin
     * @param array $destination
     * @param float $radio
     * @return bool
     */
    public static function isWithin(array $origin, array $destination, float $radio=5.0): bool
    {

        $lat0 = $origin[0];
        $lng0 = $origin[1];

        $lat1 = $destination[0];
        $lng1 = $destination[1];

        $rlat0 = deg2rad($lat0);
        $rlng0 = deg2rad($lng0);
        $rlat1 = deg2rad($lat1);
        $rlng1 = deg2rad($lng1);

        $latDelta = $rlat1 - $rlat0;
        $lonDelta = $rlng1 - $rlng0;

        $distance = 6371 * 2 * asin(
                sqrt(
                    cos($rlat0) * cos($rlat1) * pow(sin($lonDelta / 2), 2) +
                    pow(sin($latDelta / 2), 2)
                )
            );
        $distance=round($distance, 2);
        return $distance<=$radio;
    }

    public function nearbyVehicles($location){

    }
}
