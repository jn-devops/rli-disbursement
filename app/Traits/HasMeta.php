<?php

namespace App\Traits;

use Spatie\SchemalessAttributes\Casts\SchemalessAttributes;
use Illuminate\Database\Eloquent\Builder;

trait HasMeta
{
    protected array $schemalessAttributes = ['meta'];

    public function initializeHasMeta()
    {
        $this->mergeFillable(['meta']);
        $this->mergeCasts([
            'meta' => SchemalessAttributes::class
        ]);
        $this->setHidden(array_merge($this->getHidden(), ['meta']));
    }

    public function scopeWithMeta(): Builder
    {
        return $this->meta->modelScope();
    }
}
