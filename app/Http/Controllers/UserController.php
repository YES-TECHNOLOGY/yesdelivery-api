<?php

namespace App\Http\Controllers;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\File;
use App\Models\User;
use App\Rules\isCedula;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        Controller::verifyPermissions($request->user(),'GET','/users');
        if(!isset($request->limit))
            $request->limit=$this->limit_pagination;

        $users= User::paginate($request->limit);
        foreach ($users as $user) {
            $this->generatePhotographyUrl($user);
            $this->generateLicenceUrl($user);
        }
        return $this->response('false',Response::HTTP_OK,'200 OK',$users,true);
    }


    /**
     * Store a newly created user in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        Controller::verifyPermissions($request->user(),'POST','/users');
        $data=[];
        $edit_permission=[
            'identification',
            'ruc',
            'name',
            'lastname',
            'email',
            'gender',
            'password',
            'verified',
            'active',
            'cod_rol'
        ];

        foreach ($edit_permission as $d){
            if(isset($request->$d)){
                $data[$d]=$request->$d;
            }
        }

        $validate=\Validator::make($data,[
            'identification'=>['required','unique:users',new isCedula],
            'name'=> 'required',
            'lastname'=> 'required',
            'email'=> 'email|unique:users|required',
            'gender'=>'in:female,male,other|required',
            'password'=>'required',
            'verified'=>'boolean',
            'active'=>'boolean',
            'cod_rol'=>'exists:rols,cod_rol|required'
        ],$this->messages);

        if ($validate->fails())
        {
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
        }

        $data['password']=bcrypt($data['password']);


        $user = User::create($data);
        $log="The user '".$request->user()->id."' create user '$user->id'";
        $this->log('info',$log,'web',$request->user());
        return $this->response(false, Response::HTTP_CREATED, '201 Created',$user);

    }

    /**
     * Display the specified user.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request,$id): JsonResponse
    {
        Controller::verifyPermissions($request->user(),'GET','/users/{user}');
        $user=User::findOrFail($id);
        $this->generatePhotographyUrl($user);
        $this->generateLicenceUrl($user);
        return $this->response('false',Response::HTTP_OK,'200 OK',$user);
    }


    /**
     * Update the specified user in storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user=User::findOrFail($id);
        Controller::verifyPermissions($request->user(),'PUT','/users/{id}');
        $data=[];
        $edit_permission=[
            'identification',
            'ruc',
            'name',
            'lastname',
            'email',
            'gender',
            'password',
            'verified',
            'active',
            'cod_rol'
        ];

        foreach ($edit_permission as $d){
            if(isset($request->$d)){
                $data[$d]=$request->$d;
            }
        }

        $validate=\Validator::make($data,[
            'identification'=>['required','unique:users,identification,'.$user->id,new isCedula],
            'name'=> 'required',
            'lastname'=> 'required',
            'email'=> 'email|unique:users,email,'.$user->id.'|required',
            'gender'=>'in:female,male,other|required',
            'password'=>'required',
            'verified'=>'boolean',
            'active'=>'boolean',
            'cod_rol'=>'exists:rols,cod_rol|required'
        ],$this->messages);

        if ($validate->fails())
        {
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
        }

        $data['password']=bcrypt($data['password']);

        if($user->update($data)){
            $log="The user '".$request->user()->id."' updated user '$user->id'";
            $this->log('info',$log,'web',$request->user());

            return $this->response('false', Response::HTTP_OK, '200 OK', $user);
        }

        return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');

    }

    /**
     * Remove the specified user from storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request,$id): JsonResponse
    {
        Controller::verifyPermissions($request->user(),'DELETE','/users/{id}');
        if($request->user()->id!=$id){
            try {
                User::findOrFail($id)->delete();
                return $this->response('false', Response::HTTP_OK, '200 OK');
            }catch (\Exception $e){
                return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
            }
        }
        return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
    }


    /**
     * Generate access url for photography
     *
     * @param User $d
     */
    private function generatePhotographyUrl(User $d){
        if(isset($d->photography)){
            $image=File::find($d->photography);
            $d->photography=FileController::generateImageUrl($image);
        }else{
            $email = $d->email;
            $grav_url = "https://www.gravatar.com/avatar/" . md5( strtolower( trim( $email ) ) );
            $d->photography=$grav_url;
        }
    }

    /**
     * Generate access url for licence photography
     *
     * @param User $d
     * @return void
     */
    private function generateLicenceUrl(User $d): void
    {
        if(isset($d->driving_license_photography)){
            $image=File::find($d->driving_license_photography);
            $d->driving_license_photography=FileController::generateImageUrl($image);
        }
    }
}
