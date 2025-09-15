<?php

// app/Http/Requests/StoreEpisodeWithContentRequest.php
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEpisodeWithContentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // EPISODE
            'series_id' => ['required','exists:series,id'],
            'season_id' => ['required','exists:seasons,id'],
            'episode_number' => [
                'required','integer','min:1',
                Rule::unique('episodes','episode_number')
                    ->where(fn($q)=>$q->where('season_id',$this->input('season_id')))
            ],
            'title' => ['required','string','max:255'],
            'slug' => [
                'nullable','string','max:255','regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('episodes','slug')
                    ->where(fn($q)=>$q->where('season_id',$this->input('season_id')))
            ],
            'synopsis' => ['nullable','string'],
            'status' => ['nullable','in:draft,scheduled,published,archived'],
            'release_date' => ['nullable','date'],
            'runtime_minutes' => ['nullable','integer','min:1'],

            // CONTENT (most fields go here)
            'genre_id' => ['nullable','exists:genres,id'],
            'director_name' => ['nullable','string','max:255'],
            'description' => ['nullable','string'],
            'publish' => ['nullable', Rule::in(['public','private','schedule'])],
            'schedule_date' => ['nullable','date'],    // front-end splits date/time/zone
            'schedule_time' => ['nullable','date_format:H:i'],
            'schedule_tz'   => ['nullable','timezone'],
            'type'          => ['nullable','string','max:255'],

            // FILES
            'video1'      => ['nullable','file','mimetypes:video/*','max:204800'], // 200MB example
            'profile_pic' => ['nullable','image','max:5120'],  // 5MB
            'image'       => ['nullable','image','max:5120'],
        ];
    }
}
