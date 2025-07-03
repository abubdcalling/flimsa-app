<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class EpisodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'episode_number' => $this->episode_number,
            'duration' => $this->duration,
            'release_date' => $this->release_date,
            'serie' => $this->whenLoaded('serie', fn() => [
                'id' => $this->serie->id,
                'title' => $this->serie->title,
            ]),
            'season' => $this->whenLoaded('season', fn() => [
                'id' => $this->season->id,
                'title' => $this->season->title,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
