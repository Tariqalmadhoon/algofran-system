<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => data_get($this->resource, 'id'), 'source' => data_get($this->resource, 'source'), 'type' => data_get($this->resource, 'type'), 'title' => data_get($this->resource, 'title'), 'description' => data_get($this->resource, 'description'), 'starts_at' => data_get($this->resource, 'start')?->toIso8601String(), 'ends_at' => data_get($this->resource, 'end')?->toIso8601String(), 'all_day' => (bool) data_get($this->resource, 'all_day'), 'location' => data_get($this->resource, 'location'), 'halaqa' => data_get($this->resource, 'halaqa')];
    }
}
