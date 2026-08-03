<?php

namespace Xitara\Nexus\Traits;

use Illuminate\Database\Eloquent\Model;
use Xitara\Nexus\Models\ScopedFile;

trait HasScopedAttachments
{
    public function makeRelation(string $name) : ?Model
    {
        $relatedModel = parent::makeRelation($name);

        if (!$relatedModel instanceof ScopedFile) {
            return $relatedModel;
        }

        $definition = $this->getRelationDefinition($name);

        $relatedModel->setStoragePath(
            $definition['path'] ?? null
        );

        return $relatedModel;
    }
}
