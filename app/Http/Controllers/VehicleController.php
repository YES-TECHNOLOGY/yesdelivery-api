<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    /**
     * Display a listing of the user's vehicles.
     *
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
            'registration_photography'
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
            'color'=>'required|max:50'
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
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
        }

        $data=[];
        $edit_permission =[
            'registration_number',
            'brand',
            'model',
            'year_manufacture',
            'color',
            'active'
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
            'color'=>'max:50|min:1'
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
        return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
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
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
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
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
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
}
