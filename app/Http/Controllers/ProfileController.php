<?php

namespace App\Http\Controllers;

use App\Rules\correctPassword;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use function PHPUnit\Framework\isNull;

class ProfileController extends Controller
{

    /**
     * Display authenticated user information.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $data= $data_user=$request->user();
        $log="The user '".$data_user->id."' consulted his information LoggendIn.";
        $this->log('info',$log,'web',$data_user);
        return $this->response('false',Response::HTTP_OK,'200 OK',$data);
    }

    /**
     * Update the user logged in storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $user=$request->user();
        $data=[];

        $edit_permission=[
            'name',
            'lastname',
            'email',
            'gender',
            'password',
            'confirm_password',
            'new_password'
        ];

        foreach ($edit_permission as $d){
            if(isset($request->$d)){
                $data[$d]=$request->$d;
            }
        }

        if(isset($request->name)&&isNull($request->name)){
            $validate=\Validator::make($request->all(),[
                'name'=>'required'
            ],$this->messages);
            if ($validate->fails())
            {
                return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
            }
        }

        if(isset($request->lastname)&&isNull($request->lastname)){
            $validate=\Validator::make($request->all(),[
                'lastname'=>'required'
            ],$this->messages);
            if ($validate->fails())
            {
                return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
            }
        }

        if(isset($request->new_password)&&isNull($request->new_password)){
            $validate=\Validator::make($data,[
                "password"=>[new correctPassword],
                'confirm_password'=>'required',
                'new_password'=>'string|same:confirm_password'
            ],$this->messages);

            if ($validate->fails())
            {
                return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
            }
            $data['password']=bcrypt($data['new_password']);
        }

        $validate=\Validator::make($data,[
            'email'=> 'email|unique:users,email,'.$user->id,
            'gender'=>'in:female,male,other',
        ],$this->messages);

        if ($validate->fails())
        {
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
        }


        if($user->update($data)){
            $log="The user '".$request->user()->id."' updated user '$user->id'";
            $this->log('info',$log,'web',$request->user());

            return $this->response('false', Response::HTTP_OK, '200 OK');
        }

        return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');

    }

    /**
     * Update photography in the user logged in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePhotography(Request $request): JsonResponse
    {

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
                    'photography'=>$created->id_file
                ];
                $request->user()->update($data);
                $log="The user '".$request->user()->id."' update your photography image.";
                $this->log('info',$log,'web',$request->user());
                return $this->response('false', Response::HTTP_OK, '200 OK');
            }
        }
        return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
    }

    /**
     * Update licence photography in the user logged in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateDrivingLicensePhotography(Request $request): JsonResponse
    {

        if($request->hasFile('license')){
            $file = $request->file('license');
            $validate = \Validator::make(
                array(
                    'file' => $file,
                ),
                array(
                    'file' => 'mimes:jpg, jpeg, png'
                ),
                $this->messages
            );

            if ($validate->fails())
            {
                return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
            }

            $created= FileController::saveFile($file,$request->user(),'license');
            if($created){
                $data=[
                    'driving_license_photography'=>$created->id_file
                ];
                $request->user()->update($data);
                $log="The user '".$request->user()->id."' update your driving photography image.";
                $this->log('info',$log,'web',$request->user());
                return $this->response('false', Response::HTTP_OK, '200 OK');
            }
        }
        return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
    }
}
