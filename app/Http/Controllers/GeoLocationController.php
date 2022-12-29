<?php

namespace App\Http\Controllers;

use App\Models\OperateCity;
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
    public static function isWithin(array $origin, array $destination, float $radio=1): bool
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

    public static function getCityLocatedClient($location_client,$type){
        $city =OperateCity::where('type','=',$type)->get();
        foreach ($city as  $value) {
            $polygon=$value->polygon->features[0]->geometry->coordinates[0];
            $point=array('lat'=>$location_client['lat'],'lng'=>$location_client['lng']);
            $isWithinPolygon=GeoLocationController::isWithinPolygon($point,$polygon,false);
            if($isWithinPolygon){
                return $value;
            }
        }
        return false;
    }

    public static function isWithinPolygon($point=array(), $polygon=array(), $pointOnVertex = true) {
        $vertices = [];
        foreach ($polygon as $vertex) {
            $vertices[] = ['lat' => $vertex[1],
                'lng' => $vertex[0]
            ];
        }

        $intersections = 0;
        $vertices_count = count($vertices);
        for ($i=1; $i < $vertices_count; $i++) {
            $vertex1 = $vertices[$i-1];
            $vertex2 = $vertices[$i];
            if ($vertex1['lat'] == $vertex2['lat'] && $vertex1['lng'] == $vertex2['lng']) {
                if ($point == $vertex1) {
                    return $pointOnVertex;
                }
                continue;
            }
            if ($point['lat'] > min($vertex1['lat'], $vertex2['lat']) && $point['lat'] <= max($vertex1['lat'], $vertex2['lat']) && $point['lng'] <= max($vertex1['lng'], $vertex2['lng']) && $vertex1['lat'] != $vertex2['lat']) {
                $xinters = ($point['lat']-$vertex1['lat'])*($vertex2['lng']-$vertex1['lng'])/($vertex2['lat']-$vertex1['lat'])+$vertex1['lng'];
                if ($xinters == $point['lng']) {
                    return $pointOnVertex;
                }
                if ($vertex1['lng'] == $vertex2['lng'] || $point['lng'] <= $xinters) {
                    $intersections++;
                }
            }
        }

        if ($intersections % 2 != 0) {
            return true;
        } else {
            return false;
        }
    }
}
