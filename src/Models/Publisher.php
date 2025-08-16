<?php

namespace Ophim\Core\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Backpack\Settings\app\Models\Setting;
use Ophim\Core\Contracts\TaxonomyInterface;
use Hacoidev\CachingModel\Contracts\Cacheable;
use Hacoidev\CachingModel\HasCache;
use Illuminate\Database\Eloquent\Model;
use Ophim\Core\Contracts\SeoInterface;
use Ophim\Core\Traits\HasFactory;
use Ophim\Core\Traits\HasTitle;
use Ophim\Core\Traits\HasDescription;
use Ophim\Core\Traits\HasKeywords;
use Ophim\Core\Traits\HasUniqueName;
use Ophim\Core\Traits\Sluggable;
use Illuminate\Support\Str;
use Artesaos\SEOTools\Facades\JsonLdMulti;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\TwitterCard;

class Publisher extends Model implements TaxonomyInterface, Cacheable, SeoInterface
{
    use CrudTrait;
    use Sluggable;
    use HasUniqueName;
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

    protected $table = 'publishers';
    protected $guarded = ['id'];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    public static function primaryCacheKey(): string
    {
        return 'slug';
    }

    public function getUrl()
    {
        return route('publishers.manga.index', $this->slug);
    }

    protected function titlePattern(): string
    {
        return Setting::get('site_publisher_title', '');
    }

    protected function descriptionPattern(): string
    {
        return Setting::get('site_publisher_des', '');
    }

    protected function keywordsPattern(): string
    {
        return Setting::get('site_publisher_key', '');
    }

    public function generateSeoTags()
    {
        $seo_title = $this->getTitle();
        $seo_des = Str::limit($this->getDescription(), 150, '...');
        $seo_key = $this->getKeywords();

        SEOMeta::setTitle($seo_title, false)
            ->setDescription($seo_des)
            ->addKeyword([$seo_key])
            ->setCanonical($this->getUrl())
            ->setPrev(request()->root())
            ->setPrev(request()->root());

        OpenGraph::setSiteName(setting('site_meta_siteName'))
            ->setTitle($seo_title, false)
            ->addProperty('type', 'website')
            ->addProperty('locale', 'vi-VN')
            ->addProperty('url', $this->getUrl())
            ->setDescription($seo_des)
            ->addImages([$this->thumb_url]);

        TwitterCard::setSite(setting('site_meta_siteName'))
            ->setTitle($seo_title, false)
            ->setType('summary')
            ->setImage($this->thumb_url)
            ->setDescription($seo_des)
            ->setUrl($this->getUrl());

        JsonLdMulti::newJsonLd()
            ->setSite(setting('site_meta_siteName'))
            ->setTitle($seo_title, false)
            ->setType('WebPage')
            ->setDescription($seo_des)
            ->setUrl($this->getUrl());
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