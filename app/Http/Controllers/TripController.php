<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\OperateCity;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TripController extends Controller
{

    public function show(Request $request,$id){
       return $request->user()->trips()->get();
        $trip=Trip::findOrFail($id);
        return $trip;
    }

    /**
     * Calculate the price of the trip
     *
     * @param Trip $trip
     * @return array
     */
    public static function totalPrice(Trip $trip): array
    {
        $total_price=[];
        $operate_city= $trip->conversation->operateCity;
        $trip_duration=Carbon::parse($trip->start_time)->diffInMinutes($trip->end_time);
        $time= now();

        if($time->between($operate_city->night_start_time,$operate_city->night_end_time)) {
            $total_price['distance_price'] = round($trip->distance/1000*$operate_city->day_km_price,2);
            $total_price['time_price'] = round($trip_duration*$operate_city->day_min_price,2);
        }else{
            $total_price['distance_price'] = round($trip->distance/1000*$operate_city->night_km_price,2);
            $total_price['time_price'] = round($trip_duration*$operate_city->night_min_price,2);
        }

        if(($total_price['distance_price']+$total_price['time_price'])<$trip->minimum_price){
            $total_price['distance_price']=round($trip->minimum_price/2,2);
            $total_price['time_price']=round($trip->minimum_price/2,2);
        }

        $total_price['adicional_price']=$operate_city->adicional_price??0;
        $total_price['total'] = round($total_price['distance_price']+$total_price['time_price']+$total_price['adicional_price'],2);
        return $total_price;
    }

    /**
     * Calculate the price of the trip whit order
     *
     * @param Trip $trip
     * @param $duration
     * @param $distance
     * @param $order_price
     * @return array
     */
    public static function totalPriceOrder(Trip $trip,$duration,$distance,$order_price): array
    {
        $total_price=[];
        $operate_city= $trip->conversation->operateCity;
        $time= now();
        if($time->between($operate_city->night_start_time,$operate_city->night_end_time)) {
            $total_price['distance_price'] = round($distance/1000*$operate_city->day_km_price,2);
            $total_price['time_price'] = round($duration/60*$operate_city->day_min_price,2);
        }else{
            $total_price['distance_price'] = round($distance/1000*$operate_city->night_km_price,2);
            $total_price['time_price'] = round($duration/60*$operate_city->night_min_price,2);
        }

        if(($total_price['distance_price']+$total_price['time_price'])<$trip->minimum_price){
            $total_price['distance_price']=round($trip->minimum_price/2,2);
            $total_price['time_price']=round($trip->minimum_price/2,2);
        }

        $total_price['adicional_price']=$operate_city->adicional_price??0;
        $total_price['total_order']=$order_price;
        $total_price['total'] = round($total_price['distance_price']+$total_price['time_price']+$total_price['adicional_price']+$total_price['total_order'],2);
        return $total_price;
    }
}
