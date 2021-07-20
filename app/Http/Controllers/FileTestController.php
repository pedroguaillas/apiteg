<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class FileTestController extends Controller
{
    public function setFileAttribute(Request $request)
    {
        // Storage::disk('do')->put('cert/hello.txt', 'Word');

        // $request->file('file')->store('cert', 'do');
        // $request->file('file')->storeAs('cert', 'ruc.' . $request->file('file')->extension(), 'do');
        $request->file->storeAs('cert', 'ruc.' . $request->file->extension());

        // $destination_path = config("filesystems.disks.do.folder");

        // $dir_file = Storage::disk('do')->('app' . DIRECTORY_SEPARATOR . 'signs') . DIRECTORY_SEPARATOR . $certname;

        // $request->file('cert')->move($destination_path, 'art.12');

        return response()->json(['msm' => 'Correcto']);
    }

    public function getFile()
    {
        // $document = Document::where('id', '=', $id)->firstOrFail();
        // $file = Storage::disk('do_spaces')->get($document->file);
        $file = Storage::get('cert/1802685758001.p12');
        $mimetype = \GuzzleHttp\Psr7\mimetype_from_filename('cert/1802685758001.p12');
        $headers = [
            'Content-Type' => $mimetype,
        ];
        return response($file, 200, $headers);
    }
}
