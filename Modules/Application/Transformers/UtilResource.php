<?php

namespace Modules\Application\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class UtilResource extends JsonResource
{

    private $error;
    private $statusCode;

    public function __construct($resouce, $error, $statusCode)
    {
        parent::__construct($resouce);
        
        $this->error = $error;
        $this->statusCode = $statusCode;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
    */

    public function toArray($request)
    {
        return [
            "error" => $this->error,
            "statusCode" => $this->statusCode,
            "responseBody" => $this->resource
        ];
    }
}