<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Scoped route binding (mis. kelas/{pengampu}/materi/{materi}) secara default
 * mencari relasi berbentuk jamak bahasa Inggris ("materis"). Nama relasi di
 * proyek ini tidak dijamakkan, jadi pakai nama parameter apa adanya atau
 * pemetaan khusus di $relasiAnakRoute.
 */
trait RelasiAnakTunggal
{
    protected function childRouteBindingRelationshipName($childType): string
    {
        return property_exists($this, 'relasiAnakRoute') && isset($this->relasiAnakRoute[$childType])
            ? $this->relasiAnakRoute[$childType]
            : Str::camel($childType);
    }
}
