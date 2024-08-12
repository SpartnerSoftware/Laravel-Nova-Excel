<?php

namespace Maatwebsite\LaravelNovaExcel\Requests;

use Illuminate\Support\Collection;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\ActionRequest;
use Laravel\Nova\Resource;

class ExportResourceActionRequest extends ActionRequest implements ExportActionRequest
{
    use WithIndexFields;
    use WithHeadingFinder;

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|mixed
     */
    public function toExportQuery()
    {
        return $this->toSelectedResourceQuery()->when(!$this->forAllMatchingResources(), function ($query) {
            $query->whereKey(
                is_array($this->resources) ? $this->resources : explode(',', $this->resources)
            );
        });
    }

    /**
     * @param  \Laravel\Nova\Resource  $resource
     * @return Collection|Field[]
     */
    public function resourceFields(Resource $resource): Collection
    {
        return $resource->indexFields($this);
    }

    /**
     * Determine if the request is for all matching resources.
     *
     * @return bool
     */
    public function forAllMatchingResources()
    {
        return $this->resources === 'all';
    }
}
