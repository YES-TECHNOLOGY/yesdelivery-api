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
        $data_user=$request->user();
        $data_user['rol']=$data_user->rol;
        $data_user['nationality']=$data_user->nationality;
        $data_user['dpa']=$data_user->dpa;
        $log="The user '".$data_user->id."' consulted his information LoggendIn.";
        $this->log('info',$log,'web',$data_user);
        return $this->response('false',Response::HTTP_OK,'200 OK',$data_user);
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
        $validator=[];
        $validations=[
            'type_identification'=>'in:cedula,visa,passport|required',
            'identification'=>['required','unique:users,identification,'.$user->id],
            'name'=> 'required',
            'lastname'=> 'required',
            'email'=> 'email|unique:users,email,'.$user->id.'|required',
            'gender'=>'in:female,male,other|required',
            'cellphone'=>'required|min:9|max:10',
            'date_birth'=>'date|required',
            'cod_nationality'=>'exists:countries,id|required',
            'cod_dpa'=>'exists:dpas,cod_dpa|required',
            'address'=>'required',
            'size'=>'in:XS,S,M,L,XL,XXL,Other',
            'password'=>'required',
            'verified'=>'boolean',
            'active'=>'boolean',
            'cod_rol'=>'exists:rols,cod_rol|required',
        ];


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
                $validator[$d]=$validations[$d];
            }
        }


        $validate=\Validator::make($data,$validator,$this->messages);

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
    public function updateIdentificationPhotography(Request $request): JsonResponse
    {

        if($request->hasFile('license')){
            $file = $request->file('license');
            $side=$request->side;
            $validate = \Validator::make(
                array(
                    'file' => $file,
                    'side'=>$side
                ),
                array(
                    'file' => 'mimes:jpg, jpeg, png|required',
                    'side'=>'required|in:front,back'
                ),
                $this->messages
            );

            if ($validate->fails())
            {
                return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
            }

            $created= FileController::saveFile($file,$request->user(),'license');
            if($created){
                if($side=='front')
                    $data=[
                        'identification_front_photography'=>$created->id_file
                    ];
                else
                    $data=[
                        'identification_back_photography'=>$created->id_file
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
