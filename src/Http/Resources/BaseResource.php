<?php

namespace Okami101\LaravelAdmin\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $attributes = parent::toArray($request);

        /**
         * Media API generator only if media included
         */
        if (! empty($attributes['media']) && $this->resource instanceof HasMedia) {
            $this->resource->registerMediaCollections();

            /** @var MediaCollection $collection */
            collect($this->resource->mediaCollections)->each(function (MediaCollection $collection) use (&$attributes) {
                /** @var Collection $media */
                $media = $this->resource->getMedia($collection->name);

                if (! $collection->singleFile) {
                    foreach ($media as $file) {
                        $attributes[$collection->name][] = $this->getVersions($file);
                    }

                    return;
                }

                if ($file = $media->first()) {
                    $attributes[$collection->name] = $this->getVersions($file);
                }
            });

            unset($attributes['media']);
        }

        return $attributes;
    }

    private function getVersions(Media $file)
    {
        $attributes = [
            'id' => $file->id,
            'name' => $file->name,
            'file_name' => $file->file_name,
            'url' => $file->getFullUrl(),
        ];

        $conversions = $file->getMediaConversionNames();

        $attributes['thumbnails'] = collect($conversions)->mapWithKeys(function ($c) use ($file) {
            return [$c => $file->getFullUrl($c)];
        })->toArray();

        return $attributes;
    }
}
