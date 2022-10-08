<?php

namespace App\Http\Controllers;

use App\Models\OperateCity;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class OperateCityController extends Controller
{
    /**
     * Display a listing of to operate cities.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Controller::verifyPermissions($request->user(),'GET','/cities');
        if(!isset($request->limit))
            $request->limit=$this->limit_pagination;

        $cities= OperateCity::paginate($request->limit);
        $cities->each(function (&$city){
            $city['dpa']=$city->dpa;
        });

        return $this->response('false',Response::HTTP_OK,'200 OK',$cities,true);
    }


    /**
     * Store a newly created operated city in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        Controller::verifyPermissions($request->user(),'POST','/cities');
        $data=[];
        $edit_permission=[
            'type',
            'minimum_price',
            'night_km_price',
            'day_km_price',
            'night_min_price',
            'day_min_price',
            'additional_price',
            'night_start_time',
            'night_end_time',
            'active',
            'comment',
            'cod_dpa',
        ];

        foreach ($edit_permission as $permission) {
            if(isset($request->$permission))
                $data[$permission]=$request->$permission;
        }

        $validate=\Validator::make($data,[
            'type'=>'required|max:255|unique:operate_cities,type,'.$data['type'].',cod_dpa,cod_dpa,'.$data['cod_dpa'],
            'minimum_price'=>'required|numeric',
            'night_km_price'=>'required|numeric',
            'day_km_price'=>'required|numeric',
            'night_min_price'=>'required|numeric',
            'day_min_price'=>'required|numeric',
            'additional_price'=>'numeric',
            'night_start_time'=>'required|date_format:H:i:s',
            'night_end_time'=>'required|date_format:H:i:s',
            'active'=>'boolean',
            'cod_dpa'=>'required|exists:dpas,cod_dpa',
        ],$this->messages);

        if ($validate->fails())
        {
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
        }

        $city=OperateCity::create($data);
        $log="The user '".$request->user()->id."' create Operate city '$city->id'";
        $this->log('info',$log,'web',$request->user());
        return $this->response(false, Response::HTTP_CREATED, '201 Created',$city);
    }

    /**
     * Display the specified operated city.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        Controller::verifyPermissions($request->user(),'GET','/cities');
        $city=OperateCity::findOrFail($id);
        $city['dpa']=$city->dpa;
        return $this->response('false',Response::HTTP_OK,'200 OK',$city);
    }


    /**
     * Update the specified operated city in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        Controller::verifyPermissions($request->user(),'PUT','/cities/{id}');
        $city=OperateCity::findOrFail($id);

        $validations=[
            'type'=>'required|max:255|unique:operate_cities,type,'.$city->id.',id,cod_dpa,'.$city->cod_dpa,
            'minimum_price'=>'required|numeric',
            'night_km_price'=>'required|numeric',
            'day_km_price'=>'required|numeric',
            'night_min_price'=>'required|numeric',
            'day_min_price'=>'required|numeric',
            'additional_price'=>'numeric',
            'night_start_time'=>'required|date_format:H:i:s',
            'night_end_time'=>'required|date_format:H:i:s',
            'cod_dpa'=>'required|exists:dpas,cod_dpa',
        ];

        $data=[];
        $edit_permission=[
            'type',
            'minimum_price',
            'night_km_price',
            'day_km_price',
            'night_min_price',
            'day_min_price',
            'additional_price',
            'night_start_time',
            'night_end_time',
            'active',
            'comment',
            'cod_dpa',
        ];

        foreach ($edit_permission as $d){
            if(isset($request->$d)){
                $data[$d]=$request->$d;
                $validator[$d]=$validations[$d];
            }
        }

        $validate=\Validator::make($data,$validator,$this->messages);

        if ($validate->fails())
        {
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
        }

        if($city->update($data)){
            $log="The user '".$request->user()->id."' updated operated '$city->id'";
            $this->log('info',$log,'web',$request->user());
            return $this->response('false', Response::HTTP_OK, '200 OK');
        }

        return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
    }

    /**
     * Remove the specified operate city from storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        Controller::verifyPermissions($request->user(),'DELETE','/cities/{id}');
        $city=OperateCity::findOrFail($id);
        if($city->delete()){
            $log="The user '".$request->user()->id."' deleted operated city '$city->id'";
            $this->log('info',$log,'web',$request->user());
            return $this->response('false', Response::HTTP_OK, '200 OK');
        }
        return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
    }
}
