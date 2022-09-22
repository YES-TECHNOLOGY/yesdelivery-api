<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RolController extends Controller
{
    /**
     * Display a listing of the roles.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        Controller::verifyPermissions($request->user(),'GET','/roles');
        if(!isset($request->limit))
            $request->limit=$this->limit_pagination;

        $data=Rol::paginate($request->limit);
        return $this->response('false',Response::HTTP_OK,'200 OK',$data,true);
    }


    /**
     * Store a newly created role in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Controller::verifyPermissions($request->user(),'POST','/roles');
        $data=[];
        $edit_permission=[
            "name",
            "detail"
        ];

        foreach ($edit_permission as $d){
            if(isset($request->$d)){
                $data[$d]=$request->$d;
            }
        }
        $validate=\Validator::make($data,[
            'name'    => 'required|unique:rols',
        ]);

        if ($validate->fails())
        {
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
        }

        $role = Rol::create($data);
        $log="The user '".$request->user()->id."' create role '$role->cod_rol'";
        $this->log('info',$log,'web',$request->user());
        return $this->response(false, Response::HTTP_CREATED, '201 Created',$role);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request,$id)
    {
        Controller::verifyPermissions($request->user(),'GET','/roles');
        try {
            $data=Rol::findOrFail($id);
            $data['access']=$access=$data->access;
            return $this->response('false', Response::HTTP_OK, '200 OK',$data);
        }catch (\Exception $e){
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $data=[];
        Controller::verifyPermissions($request->user(),'PUT','/roles/{id}');
        $rol=Rol::findOrFail($id);
        $edit_permission=[
            "name",
            "detail"
        ];


        foreach ($edit_permission as $d){
            if(isset($request->$d)){
                $data[$d]=$request->$d;
            }
        }

        $validate=\Validator::make($data,[
            'name'=> 'unique:rols,name,'.$rol->cod_rol.',cod_rol',
        ],$this->messages);

        if ($validate->fails())
        {
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
        }

        if($rol->update($data)){
            return $this->response('false', Response::HTTP_OK, '200 OK');
        }

        return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
    }

    /**
     * Remove the specified role from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request,$id)
    {
        Controller::verifyPermissions($request->user(),'DELETE','/roles/{id}');
        try {
            Rol::findOrFail($id)->delete();
            return $this->response('false', Response::HTTP_OK, '200 OK');
        }catch (\Exception $e){
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
        }
    }

    /**
     * Set new access to role
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function setAccess(Request $request, $id){
        try {
            Controller::verifyPermissions($request->user(),'PUT','/roles/{id}');
            $access['cod_access']=$request->cod_access;
            $validate=\Validator::make( $access,[
                'cod_access'    => 'required',
            ],$this->messages);

            if ($validate->fails())
            {
                return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
            }
            $role=Rol::findOrFail($id);
            $role->access()->attach($access['cod_access']);
            return $this->response('false', Response::HTTP_OK, '200 OK');
        }catch (\Exception $e){
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
        }
    }

    /**
     * Remove access to role
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeAccess(Request $request, $id): JsonResponse
    {
        try {
            Controller::verifyPermissions($request->user(),'PUT','/roles/{id}');
            $access['cod_access']=$request->cod_access;
            $validate=\Validator::make( $access,[
                'cod_access'    => 'required',
            ],$this->messages);
            if ($validate->fails())
            {
                return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
            }
            $role=Rol::findOrFail($id);
            $role->access()->detach($access['cod_access']);
            return $this->response('false', Response::HTTP_OK, '200 OK');
        }catch (\Exception $e){
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
        }
    }
}
