<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        $properties = Property::where('status', 'live')
            ->orderBy('updated_at', 'desc')
            ->limit(5000)
            ->get();

        $latestUpdate = ($properties->first() && $properties->first()->updated_at)
            ? $properties->first()->updated_at->toAtomString()
            : now()->toAtomString();

        $staticPages = [
            ['url' => url('/'), 'priority' => '1.0', 'freq' => 'daily', 'lastmod' => $latestUpdate],
            ['url' => url('/properties'), 'priority' => '0.9', 'freq' => 'daily', 'lastmod' => $latestUpdate],
            ['url' => url('/land-search'), 'priority' => '0.9', 'freq' => 'daily', 'lastmod' => $latestUpdate],
            ['url' => url('/flat-search'), 'priority' => '0.9', 'freq' => 'daily', 'lastmod' => $latestUpdate],
            ['url' => url('/land-share-search'), 'priority' => '0.9', 'freq' => 'daily', 'lastmod' => $latestUpdate],
            ['url' => url('/commercial-search'), 'priority' => '0.9', 'freq' => 'daily', 'lastmod' => $latestUpdate],
            ['url' => url('/chahida'), 'priority' => '0.9', 'freq' => 'daily', 'lastmod' => $latestUpdate],
            ['url' => url('/sell-property'), 'priority' => '0.8', 'freq' => 'weekly', 'lastmod' => $latestUpdate],
            ['url' => url('/map-view'), 'priority' => '0.7', 'freq' => 'weekly', 'lastmod' => $latestUpdate],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach ($staticPages as $sp) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($sp['url'], ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <lastmod>{$sp['lastmod']}</lastmod>\n";
            $xml .= "    <changefreq>{$sp['freq']}</changefreq>\n";
            $xml .= "    <priority>{$sp['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        foreach ($properties as $prop) {
            $slugOrId = $prop->slug ?: $prop->id;
            $propUrl = url('/property/' . $slugOrId);
            $propLastMod = $prop->updated_at ? $prop->updated_at->toAtomString() : now()->toAtomString();
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($propUrl, ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <lastmod>" . $propLastMod . "</lastmod>\n";
            $xml .= "    <changefreq>daily</changefreq>\n";
            $xml .= "    <priority>" . ($prop->is_featured ? '0.9' : '0.8') . "</priority>\n";

            if ($prop->cover_url) {
                $xml .= "    <image:image>\n";
                $xml .= "      <image:loc>" . htmlspecialchars($prop->cover_url, ENT_XML1, 'UTF-8') . "</image:loc>\n";
                $xml .= "      <image:title>" . htmlspecialchars($prop->title, ENT_XML1, 'UTF-8') . "</image:title>\n";
                $xml .= "    </image:image>\n";
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex, follow',
        ]);
    }

    public function indexnow(string $key): Response
    {
        $expectedKey = \App\Services\SeoService::getIndexNowKey();
        if ($key === $expectedKey || $key === env('INDEXNOW_KEY', '49826f03d98748369324024874288289')) {
            return response($key, 200, [
                'Content-Type' => 'text/plain; charset=utf-8',
                'X-Robots-Tag' => 'noindex',
            ]);
        }

        abort(404);
    }
}
