<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
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

    public function viewFile($id){
        try {
            $name=$id;
            $image = File::where('name', '=', $name)->first();
            $path = $image['path'];
            $route = '../storage/app/' . $path;
            return  response()->file($route);
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
     * @param int|string $path
     * @return mixed
     */
    public static function saveFile($file, $user, string $type_file='other', int|string $path=0){
        $identification=$user->identification;
        $extension= $file->getClientOriginalExtension();
        $type=$file->getType();
        $path= $path ?$file->store("file/$path/$type_file"): $file->store("file/$identification/$type_file");
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

    public static function saveFileByContent($content, User $user, string $type_file='other',$mime_type,int|string $path=0){
        $identification=$user->identification;
        $extension=explode('/',$mime_type)[1];
        $name=uniqid(random_int(1000,9999));
        $path= $path ?"file/$path/$type_file/$name.$extension": "file/$identification/$type_file/$name.$extension";
        Storage::put($path,$content);

        $data=[
            'id_user'=>$user->id,
            'path'=>$path,
            'name'=>$name,
            'extension'=>$extension,
            'type'=>$mime_type
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
