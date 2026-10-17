<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompareController extends Controller
{
    public function index(Request $request): View
    {
        $ids = $this->parseIds($request->query('ids', ''));
        $formattedProperties = $this->fetchFormattedProperties($ids);

        $seo = \App\Services\SeoService::getOgMeta('compare');

        return view('pages.compare', [
            'properties'          => $formattedProperties,
            'formattedProperties' => $formattedProperties,
            'seo'                 => $seo,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $ids = $this->parseIds($request->input('ids', ''));
        $formattedProperties = $this->fetchFormattedProperties($ids);

        return response()->json([
            'success'    => true,
            'properties' => $formattedProperties,
        ]);
    }

    protected function parseIds(mixed $input): array
    {
        if (is_array($input)) {
            $raw = $input;
        } else {
            $raw = explode(',', (string) $input);
        }

        return array_values(array_unique(array_filter(array_map('intval', $raw))));
    }

    protected function fetchFormattedProperties(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $slice = array_slice($ids, 0, 4);
        $rawProps = Property::with(['land', 'flat', 'share', 'commercial', 'media', 'directSources'])
            ->whereIn('id', $slice)
            ->get();

        // Preserve input order
        $ordered = collect($slice)->map(function ($id) use ($rawProps) {
            return $rawProps->firstWhere('id', $id);
        })->filter()->values();

        return $ordered->map(function ($p) {
            $source = $p->directSources->first();

            $sizeLabel = match($p->type) {
                'land'       => ($p->land?->katha_size ?? $p->size ?? 0) . ' কাঠা',
                'flat'       => ($p->flat?->flat_size_sqft ?? 0) . ' বর্গফুট',
                'land_share' => ($p->share?->flat_size_sqft ?? 0) . ' বর্গফুট',
                'commercial' => ($p->commercial?->size_sqft ?? 0) . ' বর্গফুট',
                default      => $p->size ? $p->size . ' কাঠা' : 'N/A',
            };

            $unitRate = '';
            if ($p->type === 'land' && $p->land && $p->land->price_per_katha > 0) {
                $lakh = $p->land->price_per_katha / 100000;
                $unitRate = '৳' . number_format($lakh, 2) . ' লাখ / কাঠা';
            } elseif ($p->type === 'flat' && $p->flat && $p->flat->price_per_sqft > 0) {
                $unitRate = '৳' . number_format($p->flat->price_per_sqft) . ' / sqft';
            } elseif ($p->type === 'commercial' && $p->commercial && $p->commercial->price_per_sqft > 0) {
                $unitRate = '৳' . number_format($p->commercial->price_per_sqft) . ' / sqft';
            }

            return [
                'id'              => $p->id,
                'title'           => $p->title,
                'slug'            => $p->slug,
                'property_code'   => $p->property_code ?: ('AF-' . $p->id),
                'type'            => $p->type,
                'type_label'      => $p->type_label,
                'area'            => $p->area ?: 'Aftabnagar',
                'block'           => $p->block ?: '',
                'sector'          => $p->sector ?: '',
                'road_number'     => $p->road_number ?: '',
                'plot_number'     => $p->plot_number ?: '',
                'road_width_ft'   => (int) ($p->road_width_ft ?: 0),
                'size_label'      => $sizeLabel,
                'unit_rate'       => $unitRate,
                'price'           => (float) ($p->raw_price ?: $p->asking_price),
                'formatted_price' => $p->formatted_price,
                'is_negotiable'   => (bool) ($p->is_negotiable ?? true),
                'facing'          => $p->facing ?: '',
                'cover_url'       => $p->cover_url,
                'detail_url'      => route('property.show', $p->slug ?: $p->id),
                'source_type'     => $source?->source_type ?? 'direct_agent',
                'owner_name'      => $source?->owner_name ?? '',

                'land' => $p->land ? [
                    'katha_size'          => $p->land->katha_size,
                    'price_per_katha'     => $p->land->price_per_katha,
                    'is_corner'           => (bool) $p->land->is_corner,
                    'is_lake_facing'      => (bool) $p->land->is_lake_facing,
                    'boundary_wall_done'  => (bool) $p->land->boundary_wall_done,
                ] : null,

                'flat' => $p->flat ? [
                    'flat_size_sqft'      => (int) $p->flat->flat_size_sqft,
                    'bedrooms'            => (int) $p->flat->bedrooms,
                    'bathrooms'           => (int) $p->flat->bathrooms,
                    'has_lift'            => (bool) $p->flat->has_lift,
                    'parking_available'   => (bool) $p->flat->parking_available,
                    'has_gas'             => (bool) $p->flat->has_gas,
                ] : null,

                'share' => $p->share ? [
                    'flat_size_sqft'      => (int) $p->share->flat_size_sqft,
                    'share_price'         => (float) $p->share->share_price,
                    'land_share_katha'    => (float) $p->share->land_share_katha,
                ] : null,

                'commercial' => $p->commercial ? [
                    'size_sqft'           => (int) $p->commercial->size_sqft,
                    'market_name'         => $p->commercial->market_name,
                    'shop_number'         => $p->commercial->shop_number,
                    'has_lift'            => (bool) $p->commercial->has_lift,
                    'has_escalator'       => (bool) $p->commercial->has_escalator,
                    'has_central_ac'      => (bool) $p->commercial->has_central_ac,
                ] : null,
            ];
        })->all();
    }
}
