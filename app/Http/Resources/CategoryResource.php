<?php

namespace App\Http\Resources;

use App\Models\Category;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Category */
class CategoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image_url' => Media::url($this->image),
            'description' => $this->description,
            'sort' => $this->sort,
            'children' => CategoryResource::collection($this->whenLoaded('activeChildren')),
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'parent' => new CategoryResource($this->whenLoaded('parent')),
        ];
    }
}
