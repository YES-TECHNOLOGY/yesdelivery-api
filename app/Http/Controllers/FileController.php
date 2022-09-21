<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Intervention\Image\Facades\Image;


class FileController extends Controller
{
    public function viewImage(Request $request)
    {
        try {
            $image = $request->img;
            $image=explode('=',$image);
            $name=$image[0];
            $size= $image[1] ?? 's200';
            $size= explode('s',$size)[1];
            $image = File::where('name', '=', $name)->first();
                $path = $image['path'];
                $route = '../storage/app/' . $path;

            $img = Image::make($route);
            return  $img->resize($size, $size, function ($const) {
                $const->aspectRatio();
            })->response();
        }catch (\Exception $e){
            return 'Error';
        }
    }

    /**
     * Store a new file.
     *
     * @param $file
     * @param $user
     * @param string $type_file
     * @return mixed
     */
    public static function saveFile($file,$user, $type_file='other'){
        $identification=$user->identification;
        $extension= $file->getClientOriginalExtension();
        $type=$file->getType();
        $path = $file->store("file/$identification/$type_file");
        $aux=explode('/',$path);
        $name=end($aux);
        $data=[
            'id_user'=>$user->id,
            'path'=>$path,
            'name'=>explode('.',$name)[0],
            'extension'=>$extension,
            'type'=>$type
        ];
        return File::create($data);
    }

    public static function generateImageUrl(File $file){

        try {
            $uri='/image';
            return env('APP_URL').$uri."/".$file->name;
        }catch (\Exception $e){

        }
    }

}
