<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\File;
use App\Models\Location;
use App\Models\Trip;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    /**
     * Display a listing of the user's vehicles.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request):  JsonResponse
    {
        Controller::verifyPermissions($request->user(),'GET','/vehicles');
        $vehicles=$request->user()->vehicles;
        return $this->response('false',Response::HTTP_OK,'200 OK',$vehicles,false);
    }


    /**
     * Store a newly created vehicle in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        Controller::verifyPermissions($request->user(),'POST','/vehicles');
        $data=[];
        $edit_permission =[
            'registration_number',
            'brand',
            'model',
            'year_manufacture',
            'color',
            'type',
            'type_orders'
        ];

        foreach ($edit_permission as $d){
            if(isset($request->$d)){
                $data[$d]=$request->$d;
            }
        }

        $validate=Validator::make($data,[
            'registration_number'=>'required|unique:vehicles|regex:/^([A-Z]{3}-\d{3,4})$/',
            'brand'=>'required|max:50',
            'model'=>'required|max:50',
            'year_manufacture'=>'required|numeric',
            'color'=>'required|max:50',
            'type'=>'required',
            'type_orders'=>'required|in:taxi,delivery,taxi_delivery',
        ],$this->messages);

        if ($validate->fails())
        {
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
        }

        $data['cod_user']=$request->user()->id;

        $vehicle=Vehicle::create($data);

        $log="The user '".$request->user()->id."' create vehicle '$vehicle->id'";
        $this->log('info',$log,'web',$request->user());
        return $this->response(false, Response::HTTP_CREATED, '201 Created',$vehicle);

    }

    /**
     * Display the specified vehicle.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request,$id): JsonResponse
    {
        Controller::verifyPermissions($request->user(),'GET','/vehicles');
        $vehicle=$request->user()->vehicles->find($id);
        if(!$vehicle){
            return $this->response('true', Response::HTTP_NOT_FOUND, '404 NOT FOUND');
        }
        $this->generateRegistrationPhotographyUrl($vehicle);
        return $this->response('false',Response::HTTP_OK,'200 OK',$vehicle);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        Controller::verifyPermissions($request->user(),'PUT','/vehicles/{id}');
        $vehicle=$request->user()->vehicles->find($id);

        if(!$vehicle){
            return $this->response('true', Response::HTTP_NOT_FOUND, '404 NOT FOUND');
        }

        $data=[];
        $edit_permission =[
            'registration_number',
            'brand',
            'model',
            'year_manufacture',
            'color',
            'active',
            'type',
            'type_orders'
        ];

        foreach ($edit_permission as $d){
            if(isset($request->$d)){
                $data[$d]=$request->$d;
            }
        }

        $validate=Validator::make($data,[
            'registration_number'=>'unique:vehicles,registration_number,'.$vehicle->id.'|regex:/^([A-Z]{3}-\d{3,4})$/',
            'brand'=>'max:50|min:1',
            'model'=>'max:50|min:1',
            'year_manufacture'=>'numeric',
            'color'=>'max:50|min:1',
            'type'=>'required',
            'type_orders'=>'required|in:taxi,delivery,taxi_delivery',
        ],$this->messages);

        if ($validate->fails())
        {
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
        }


        if($vehicle->update($data)){
            $log="The user '".$request->user()->id."' updated vehicle '$vehicle->id'";
            $this->log('info',$log,'web',$request->user());
            return $this->response('false', Response::HTTP_OK, '200 OK');
        }

        return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request,$id): JsonResponse
    {
        Controller::verifyPermissions($request->user(),'DELETE','/vehicles/{id}');
        $vehicle=$request->user()->vehicles->find($id);

        if($vehicle){
            $vehicle->delete();
            return $this->response('false', Response::HTTP_OK, '200 OK');
        }
        return $this->response('true', Response::HTTP_NOT_FOUND, '404 NOT FOUND');
    }

    /**
     * Upload registration photography in storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateRegistrationPhotography(Request $request,$id): JsonResponse
    {
        Controller::verifyPermissions($request->user(),'POST','/vehicles');
        $vehicle=$request->user()->vehicles->find($id);

        if(!$vehicle||$vehicle->registration_photography!=null){
            return $this->response('true', Response::HTTP_NOT_FOUND, '404 NOT FOUND');
        }

        if($request->hasFile('photography')){
            $file = $request->file('photography');
            $validate = \Validator::make(
                array(
                    'file' => $file,
                ),
                array(
                    'file' => 'mimes:jpg, jpeg, png'
                )
            );

            if ($validate->fails())
            {
                return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
            }

            $created= FileController::saveFile($file,$request->user(),'photography');
            if($created){
                $data=[
                    'registration_photography'=>$created->id_file
                ];
                $vehicle->update($data);
                $log="The user '".$request->user()->id."' update your registration photography image in vehicle $vehicle->id.";
                $this->log('info',$log,'web',$request->user());
                return $this->response('false', Response::HTTP_OK, '200 OK');
            }
        }
        return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
    }

    /**
     * Delete registration photography in storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function deleteRegistrationPhotography(Request $request,$id): JsonResponse
    {
        Controller::verifyPermissions($request->user(),'PUT','/vehicles/{id}');
        $vehicle=$request->user()->vehicles->find($id);

        if(!$vehicle){
            return $this->response('true', Response::HTTP_NOT_FOUND, '404 NOT FOUND');
        }
        $data=[
            'registration_photography'=>null
        ];
        $vehicle->update($data);
        $log="The user '".$request->user()->id."' delete your registration photography image in vehicle $vehicle->id.";
        $this->log('info',$log,'web',$request->user());
        return $this->response('false', Response::HTTP_OK, '200 OK');
    }

    /**
     * Generate access url for registration photography
     *
     * @param Vehicle $d
     * @return void
     */
    private function generateRegistrationPhotographyUrl(Vehicle $d): void
    {
        if(isset($d->registration_photography)){
            $image=File::find($d->registration_photography);
            $d->registration_photography=FileController::generateImageUrl($image);
        }
    }

    /**
     * Store a newly created location of vehicle in storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function storeLocation(Request $request,$id): JsonResponse
    {
        Controller::verifyPermissions($request->user(),'POST','/vehicles');
        $vehicle=$request->user()->vehicles->find($id);

        if(!$vehicle){
            return $this->response('true', Response::HTTP_NOT_FOUND, '404 NOT FOUND');
        }

        $data=[];
        $edit_permission =[
            'latitude',
            'longitude'
        ];

        foreach ($edit_permission as $d){
            if(isset($request->$d)){
                $data[$d]=$request->$d;
            }
        }

        $validate=Validator::make($data,[
            'latitude'=>'required',
            'longitude'=>'required',
        ],$this->messages);

        if ($validate->fails())
        {
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
        }

        $data['cod_vehicle']=$vehicle->id;

        Location::create($data);

        return $this->response(false, Response::HTTP_CREATED, '201 Created');

    }

    public function connect(Request $request,$id){
        $vehicle=$request->user()->vehicles->find($id);
        if(!$vehicle){
            return $this->response('true', Response::HTTP_NOT_FOUND, '404 NOT FOUND');
        }

        if($request->action=='disconnected'){
            if($vehicle->status!='connected'){
                return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
            }
            $vehicle->status='disconnected';
            $vehicle->save();
            $log="The user '".$request->user()->id."' disconnected vehicle '$vehicle->id'";
            $this->log('info',$log,'web',$request->user());
            return $this->response('false', Response::HTTP_OK, '200 OK');
        }

        if($request->user()->vehicles->where('status','!=','disconnected')->first()){
            $error=[
                'message'=>'You have a vehicle connected',
                'code'=>'VEHICLE_CONNECTED'
            ];

            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST',$error);
        }
        if(!$vehicle->active){
            $error=[
                'message'=>'The vehicle is not active',
                'code'=>'VEHICLE_NOT_ACTIVE'
            ];
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $error);
        }

        $last_location=$vehicle->locations->last();
        if(!$last_location){
            $error=[
                'message'=>'The vehicle has not location',
                'code'=>'VEHICLE_NOT_LOCATION'
            ];
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $error);
        }
        $point=[
            'lat'=>$last_location->latitude,
            'lng'=>$last_location->longitude
        ];
        $operate_city=$request->user()->operateCities->where('active','=',true)->first();

        if(!$operate_city){
            $error=[
                'message'=>'Not operate city',
                'code'=>'NOT_OPERATE_CITY'
            ];
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $error);
        }

        $polygon=$operate_city->polygon->features[0]->geometry->coordinates[0];

        if(!GeoLocationController::isWithinPolygon($point,$polygon)){
            $error=[
                'message'=>'The vehicle is not in your operate city',
                'code'=>'VEHICLE_NOT_IN_OPERATE_CITY'
            ];
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $error);
        }

        $data=[
            'status'=>'connected'
        ];
        $vehicle->update($data);
        $log="The user '".$request->user()->id."' connect the vehicle $vehicle->id.";
        $this->log('info',$log,'web',$request->user());
        return $this->response('false', Response::HTTP_OK, '200 OK');
    }

    /**
     * Return the trip of vehicle
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function trip(Request $request, $id): JsonResponse
    {
        $vehicle=$request->user()->vehicles->find($id);
        if(!$vehicle) {
            return $this->response('true', Response::HTTP_NOT_FOUND, '404 NOT FOUND');
        }
         $trip=$vehicle->findOrfail($id)
            ->trips()
            ->where('status','=','traveling')
            ->orwhere('status','=','delivery')
            ->first();
        if(!$trip){
            return $this->response('true', Response::HTTP_NOT_FOUND, '404 NOT FOUND');
        }

        return $this->response('false', Response::HTTP_OK, '200 OK',$trip);
    }


    public function updateTrip(Request $request, $id){
        $data=[];
        $vehicle=$request->user()->vehicles->find($id);
        if(!$vehicle) {
            return $this->response('true', Response::HTTP_NOT_FOUND, '404 NOT FOUND');
        }
        $trip=$vehicle->findOrfail($id)
            ->trips()
            ->where('status','=','traveling')
            ->orwhere('status','=','delivery')
            ->first();
        if(!$trip){
            return $this->response('true', Response::HTTP_NOT_FOUND, '404 NOT FOUND');
        }

        if($trip->conversation->type_order=='delivery'){
            $validate=Validator::make($request->all(),[
                'status'=>'required|in:traveling,delivery,canceled,completed',
            ],$this->messages);

            if ($validate->fails())
            {
                return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
            }

            $location=$vehicle->locations->last();
            $remittent= $trip->conversation->phoneNumber->number;

            if($request->status=='delivery' && $trip->status=='traveling'){

                $validate=Validator::make($request->all(),[
                    'order'=>'required',
                    'price_order'=>'required|numeric',
                ],$this->messages);

                if ($validate->fails())
                {
                    return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
                }

                $data['status']='delivery';
                $data['latitude_origin']=$location->latitude;
                $data['longitude_origin']=$location->longitude;
                $data['longitude_destination']=$trip->conversation->longitude;
                $data['latitude_destination']=$trip->conversation->latitude;
                $data['start_time']=Carbon::now();
                $data['order']=$request->order;
                $data['price_order']=$request->price_order;
                $origin=[
                    $location->latitude,
                    $location->longitude,
                ];
               $destination=[
                     $trip->conversation->latitude,
                    $trip->conversation->longitude,
                ];
                $distance_matrix= GoogleGeoLocationController::distanceMatrix($origin,$destination);
                $data['estimated_distance']=$distance_matrix['rows'][0]['elements'][0]['distance']['value'];
                $data['estimated_duration']=$distance_matrix['rows'][0]['elements'][0]['duration']['value'];

                $price_trip= TripController::totalPriceOrder($trip,$data['estimated_duration'],$data['estimated_distance'],$data['price_order']);
                $data['distance_price']=$price_trip['distance_price'];
                $data['time_price']=$price_trip['time_price'];
                $data['adicional_price']=$price_trip['adicional_price'];

                $parameters=array(
                    array(
                        "type"=> "text",
                        "text"=> $data['price_order']
                    ),
                    array(
                        "type"=> "text",
                        "text"=> $data['distance_price']
                    ),
                    array(
                        "type"=> "text",
                        "text"=> $data['time_price']
                    ),
                    array(
                        "type"=> "text",
                        "text"=> $data['adicional_price']
                    ),
                    array(
                        "type"=> "text",
                        "text"=> $price_trip['total']
                    )
                );

                $image='https://www.devsoftec.com/photo_2022-11-01_10-36-40.jpg';
                WhatsAppController::sendMessageParamsTemplate($remittent,'wa_send_price_delivery',$parameters,$trip->conversation->phone_number_id,$image);
            }

            if($trip->status='traveling' && $request->status=='completed'){

                $validate=Validator::make($request->all(),[
                    'distance'=>'required|numeric',
                    'duration'=>'required|numeric',
                ],$this->messages);

                if ($validate->fails())
                {
                    return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
                }

                $data['end_time']=Carbon::now();
                $data['duration']=$request->duration;
                $data['distance']= $request->distance ?? 0;
                $data['status']='completed';
                $sms='Estimado cliente, su pedido ha sido entregado con éxito. Gracias por confiar en nosotros.';
                WhatsAppController::sendMessageText($remittent,$sms,$trip->conversation->phone_number_id);
            }

            if(!$data){
                return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
            }

            if(!$trip->update($data)){
                $log=[
                    'message'=>'Error updating trip',
                    'code'=>'ERROR_UPDATING_TRIP'
                ];
                return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST',$log);
            }
            $trip->vehicle->update(['status'=>'disconnected']);
            $trip->conversation->update(['status'=>'terminated']);

            return $this->response('false', Response::HTTP_OK, '200 OK');
        }


        $validate=Validator::make($request->all(),[
            'status'=>'required|in:traveling,delivery,canceled,completed',
            'waiting_time'=>'numeric',
            'distance'=>'numeric'
        ],$this->messages);

        if ($validate->fails())
        {
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
        }

        $location=$vehicle->locations->last();

        if($request->status=='delivery' && $trip->status='traveling'){
            $data['status']='delivery';
            $data['latitude_origin']=$location->latitude;
            $data['longitude_origin']=$location->longitude;
            $data['start_time']=Carbon::now();
        }

        if($request->status=='completed'){
            $data['status']='completed';
            $data['latitude_destination']=$location->latitude;
            $data['longitude_destination']=$location->longitude;
            $data['end_time']=Carbon::now();
            $data['waiting_time']= $request->waiting_time ?? 0;
            $data['distance']= $request->distance ?? 0;
        }

        if(!$data){
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
        }

        $price_trip=TripController::totalPrice($trip);
        $data['distance_price']=$price_trip['distance_price'];
        $data['time_price']=$price_trip['time_price'];
        $data['adicional_price']=$price_trip['adicional_price'];

        if(!$trip->update($data)){
            $log=[
                'message'=>'Error updating trip',
                'code'=>'ERROR_UPDATING_TRIP'
            ];
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST',$log);
        }

        $parameters=array(
            array(
                "type"=> "text",
                "text"=> $data['distance_price']
            ),
            array(
                "type"=> "text",
                "text"=> $data['time_price']
            ),
            array(
                "type"=> "text",
                "text"=> $data['adicional_price']
            ),
            array(
                "type"=> "text",
                "text"=> $price_trip['total']
            )
        );
        $remittent= $trip->conversation->phoneNumber->number;
        $image='https://www.devsoftec.com/photo_2022-11-01_10-36-40.jpg';
        WhatsAppController::sendMessageParamsTemplate($remittent,'wa_send_price_taxi',$parameters,$trip->conversation->phone_number_id,$image);
        $trip->vehicle->update(['status'=>'disconnected']);
        $trip->conversation->update(['status'=>'terminated']);
        return $this->response('false', Response::HTTP_OK, '200 OK');
    }
}
