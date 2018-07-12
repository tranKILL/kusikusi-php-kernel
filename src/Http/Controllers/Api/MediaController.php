<?php

namespace Cuatromedios\Kusikusi\Http\Controllers\Api;

use Cuatromedios\Kusikusi\Http\Controllers\Controller;
use Cuatromedios\Kusikusi\Exceptions\ExceptionDetails;
use Cuatromedios\Kusikusi\Models\Http\ApiResponse;
use Cuatromedios\Kusikusi\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MediaController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Create the specified media, it's all equal that post entity, but this forces the parent to be the Media Container.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function post(Request $request)
    {
        try {
            // TODO: Filter the json to delete al not used data
            $information = $request->json()->all();
            if (!isset($information['parent'])) {
                $information['parent'] = 'media';
            }
            if (!isset($information['model'])) {
                $information['model'] = 'medium';
            }
            if (Gate::allows('post-entity', $information['parent']) === true) {
                $entityPostedId = Entity::post($request->json()->all());
                return (new ApiResponse($entityPostedId, TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
            return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
        }
    }

    /**
     * Gets the raw request and search for the corresponing entity to know its model.
     *
     * @param $request \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function upload($id, Request $request)
    {
        try {
            try {
                $entity = Entity::getOne($id);
            } catch (\Exception $e) {
                return (new ApiResponse(NULL, FALSE, 'Media ' . ApiResponse::TEXT_NOTFOUND, ApiResponse::STATUS_NOTFOUND))->response();
            }
            if (Gate::allows('patch-entity', $id) === true) {
                function processFile($id, $function, $file)
                {
                    $fileRead = $file->getClientOriginalExtension() ? $file->getClientOriginalExtension() : $file->guessClientExtension();
                    $format = $fileRead == 'jpeg' ? 'jpg': 'jpg';
                    $data = [
                        'id' => $id,
                        'format' => $format,
                        'size' => $file->getClientSize(),
                        'function' => $function
                    ];
                    $storageFileName = $function . '.' . $data['format'];
                    Storage::disk('media_original')->putFileAs($id, $file, $storageFileName);
                    Storage::disk('media_processed')->deleteDirectory($id);
                    return $data;
                }

                $data = NULL;
                if ($request->hasFile('thumb') && $request->file('thumb')->isValid()) {
                    $data = processFile($id, 'thumb', $request->file('thumb'));
                }
                if ($request->hasFile('file') && $request->file('file')->isValid()) {
                    $data = processFile($id, 'file', $request->file('file'));
                    $entity['data'] = $data;
                    $entity->save();
                }
                if (NULL === $data) {
                    return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_BADREQUEST, ApiResponse::STATUS_BADREQUEST))->response();
                }
                return (new ApiResponse($data, TRUE))->response();

            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
            return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
        }
    }

    public function delete($id)
    {
        try {
            try {
                $entity = Entity::getOne($id);
            } catch (\Exception $e) {
                return (new ApiResponse(NULL, FALSE, 'Media ' . ApiResponse::TEXT_NOTFOUND, ApiResponse::STATUS_NOTFOUND))->response();
            }
            if (Gate::allows('delete-entity', $id) === true) {
                $deletedMedia = DB::table('media')->where('entity_id', $id);
                $deletedMedia->delete();
                Storage::disk('media_original')->deleteDirectory($id);
                Storage::disk('media_processed')->deleteDirectory($id);
                return (new ApiResponse($entity['id'], TRUE))->response();
            } else {
                return (new ApiResponse(NULL, FALSE, ApiResponse::TEXT_FORBIDDEN, ApiResponse::STATUS_FORBIDDEN))->response();
            }
        } catch (\Exception $e) {
            $exceptionDetails = ExceptionDetails::filter($e);
            return (new ApiResponse(NULL, FALSE, $exceptionDetails['info'], $exceptionDetails['info']['code']))->response();
        }
    }
}
