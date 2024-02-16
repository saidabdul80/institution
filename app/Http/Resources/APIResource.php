<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class APIResource extends JsonResource
{
    private $error;
    private $statusCode;

    public function __construct($resource, $error, $statusCode)
    {
        parent::__construct($resource);
        $this->error = $error;
        $this->statusCode = $statusCode;
    }

    /**
     * Transform the resource into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function toResponse($request)
    {
        return response([
            "error" => $this->error,
            "status" => $this->statusCode,
            "data" => $this->resource
        ], $this->statusCode);
    }
}
