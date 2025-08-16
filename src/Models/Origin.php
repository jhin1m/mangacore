<?php

namespace Ophim\Core\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Backpack\Settings\app\Models\Setting;
use Ophim\Core\Contracts\TaxonomyInterface;
use Hacoidev\CachingModel\Contracts\Cacheable;
use Hacoidev\CachingModel\HasCache;
use Illuminate\Database\Eloquent\Model;
use Ophim\Core\Contracts\SeoInterface;
use Ophim\Core\Traits\ActorLog;
use Ophim\Core\Traits\HasFactory;
use Ophim\Core\Traits\HasTitle;
use Ophim\Core\Traits\HasDescription;
use Ophim\Core\Traits\HasKeywords;
use Ophim\Core\Traits\Sluggable;
use Illuminate\Support\Str;
use Artesaos\SEOTools\Facades\JsonLdMulti;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;

class Origin extends Model implements TaxonomyInterface, Cacheable, SeoInterface
{
    use CrudTrait;
    use ActorLog;
    use Sluggable;
    use HasFactory;
    use HasCache;
    use HasTitle;
    use HasDescription;
    use HasKeywords;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'origins';
    protected $guarded = ['id'];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    public static function primaryCacheKey(): string
    {
        $site_routes = setting('site_routes_origin', '/xuat-xu/{origin}');
        if (strpos($site_routes, '{origin}')) return 'slug';
        if (strpos($site_routes, '{id}')) return 'id';
        return 'slug';
    }

    public function getUrl($withDomain = true)
    {
        $params = [];
        $site_routes = setting('site_routes_origin', '/xuat-xu/{origin}');
        if (strpos($site_routes, '{origin}')) $params['origin'] = $this->slug;
        if (strpos($site_routes, '{id}')) $params['id'] = $this->id;
        return route('origins.manga.index', $params, $withDomain);
    }

    protected function titlePattern(): string
    {
        return Setting::get('site_origin_title', '');
    }

    protected function descriptionPattern(): string
    {
        return Setting::get('site_origin_des', '');
    }

    protected function keywordsPattern(): string
    {
        return Setting::get('site_origin_key', '');
    }

    public function generateSeoTags()
    {
        $seo_title = $this->seo_title ?: $this->getTitle();
        $seo_des = Str::limit($this->seo_des ?: $this->getDescription(), 150, '...');
        $seo_key = $this->seo_key ?: $this->getKeywords();
        $manga_cover_url = '';
        $manga_banner_url = '';
        $updated_at = '';
        if(count($this->manga)) {
            $manga_cover_url = filter_var($this->manga->last()->cover_image, FILTER_VALIDATE_URL) ? $this->manga->last()->cover_image : request()->root() . $this->manga->last()->cover_image;
            $manga_banner_url = filter_var($this->manga->last()->banner_image, FILTER_VALIDATE_URL) ? $this->manga->last()->banner_image : request()->root() . $this->manga->last()->banner_image;
            $updated_at = $this->manga->last()->updated_at;
        }
        $getUrl = $this->getUrl();
        $site_meta_siteName = setting('site_meta_siteName');

        SEOMeta::setTitle($seo_title, false)
            ->setDescription($seo_des)
            ->addKeyword([$seo_key])
            ->setCanonical($getUrl)
            ->setPrev(request()->root())
            ->setPrev(request()->root());

        OpenGraph::setSiteName($site_meta_siteName)
            ->setType('website')
            ->setTitle($seo_title, false)
            ->addProperty('locale', 'vi-VN')
            ->addProperty('updated_time', $updated_at)
            ->addProperty('url', $getUrl)
            ->setDescription($seo_des)
            ->addImages([$manga_cover_url, $manga_banner_url]);

        TwitterCard::setSite($site_meta_siteName)
            ->setTitle($seo_title, false)
            ->setType('summary')
            ->setImage($manga_cover_url)
            ->setDescription($seo_des)
            ->setUrl($getUrl);

        JsonLdMulti::newJsonLd()
            ->setSite($site_meta_siteName)
            ->setTitle($seo_title, false)
            ->setType('WebPage')
            ->addValue('dateCreated', $updated_at)
            ->addValue('dateModified', $updated_at)
            ->addValue('datePublished', $updated_at)
            ->setDescription($seo_des)
            ->setImages([$manga_cover_url, $manga_banner_url])
            ->setUrl($getUrl);

        $breadcrumb = [];
        array_push($breadcrumb, [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => url('/')
        ]);
        array_push($breadcrumb, [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => $this->name,
            'item' => $getUrl
        ]);
        array_push($breadcrumb, [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => "Trang " . (request()->get('page') ?: 1),
        ]);
        JsonLdMulti::newJsonLd()
            ->setType('BreadcrumbList')
            ->addValue('name', '')
            ->addValue('description', '')
            ->addValue('itemListElement', $breadcrumb);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function manga()
    {
        return $this->belongsToMany(Manga::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}