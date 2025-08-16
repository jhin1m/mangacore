<?php

namespace Ophim\Core\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\Settings\app\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL as LARURL;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Ophim\Core\Models\Author;
use Ophim\Core\Models\Artist;
use Ophim\Core\Models\Catalog;
use Ophim\Core\Models\Category;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Origin;
use Ophim\Core\Models\Publisher;
use Ophim\Core\Models\Tag;
use Prologue\Alerts\Facades\Alert;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;

class SiteMapController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setRoute(config('backpack.base.route_prefix') . '/sitemap');
        CRUD::setEntityNameStrings('site map', 'site map');
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::addField(['name' => 'sitemap', 'type' => 'custom_html', 'value' => 'Sitemap sẽ được lưu tại đường dẫn: <i>' . url('/sitemap.xml') . '</i>']);
        $this->crud->addSaveAction([
            'name' => 'save_and_new',
            'redirect' => function ($crud, $request, $itemId) {
                return $crud->route;
            },
            'button_text' => 'Tạo sitemap',
        ]);

        $this->crud->setOperationSetting('showSaveActionChange', false);
    }

    public function render_styles()
    {
        $xml = view('ophim::sitemap/styles', [
            'title' => Setting::get('site_homepage_title'),
            'domain' => LARURL::to('/')
        ])->render();

        file_put_contents(public_path('main-sitemap.xsl'), $xml);
        return;
    }

    public function add_styles($file_name)
    {
        $path = public_path($file_name);
        if(file_exists($path)) {
            $content = file_get_contents($path);
            $content = str_replace('?'.'>', '?'.'>'.'<'.'?'.'xml-stylesheet type="text/xsl" href="'. LARURL::to('/') .'/main-sitemap.xsl"?'.'>', $content);
            file_put_contents($path, $content);
        }
    }

    public function store(Request $request)
    {
        $this->render_styles();
        if (!File::isDirectory('sitemap')) File::makeDirectory('sitemap', 0777, true, true);

        $sitemap_index = SitemapIndex::create();

        $sitemap_page = Sitemap::create();
        $sitemap_page->add(Url::create('/')
            ->setLastModificationDate(now())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_HOURLY)
            ->setPriority(1));
        Catalog::chunkById(100, function ($catalogs) use ($sitemap_page) {
            foreach ($catalogs as $catalog) {
                $sitemap_page->add(
                    Url::create($catalog->getUrl())
                        ->setLastModificationDate(now())
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                        ->setPriority(0.9)
                );
            }
        });
        $sitemap_page->writeToFile(public_path('sitemap/page-sitemap.xml'));
        $this->add_styles('sitemap/page-sitemap.xml');
        $sitemap_index->add('sitemap/page-sitemap.xml');

        $sitemap_categories = Sitemap::create();
        Category::chunkById(100, function ($categoires) use ($sitemap_categories) {
            foreach ($categoires as $category) {
                $sitemap_categories->add(
                    Url::create($category->getUrl())
                        ->setLastModificationDate(now())
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                        ->setPriority(0.8)
                );
            }
        });
        $sitemap_categories->writeToFile(public_path('sitemap/categories-sitemap.xml'));
        $this->add_styles('sitemap/categories-sitemap.xml');
        $sitemap_index->add('sitemap/categories-sitemap.xml');

        $sitemap_origins = Sitemap::create();
        Origin::chunkById(100, function ($origins) use ($sitemap_origins) {
            foreach ($origins as $origin) {
                $sitemap_origins->add(
                    Url::create($origin->getUrl())
                        ->setLastModificationDate(now())
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                        ->setPriority(0.8)
                );
            }
        });
        $sitemap_origins->writeToFile(public_path('sitemap/origins-sitemap.xml'));
        $this->add_styles('sitemap/origins-sitemap.xml');
        $sitemap_index->add('sitemap/origins-sitemap.xml');

        // Authors sitemap
        $sitemap_authors = Sitemap::create();
        Author::chunkById(100, function ($authors) use ($sitemap_authors) {
            foreach ($authors as $author) {
                $sitemap_authors->add(
                    Url::create($author->getUrl())
                        ->setLastModificationDate(now())
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(0.7)
                );
            }
        });
        $sitemap_authors->writeToFile(public_path('sitemap/authors-sitemap.xml'));
        $this->add_styles('sitemap/authors-sitemap.xml');
        $sitemap_index->add('sitemap/authors-sitemap.xml');

        // Artists sitemap
        $sitemap_artists = Sitemap::create();
        Artist::chunkById(100, function ($artists) use ($sitemap_artists) {
            foreach ($artists as $artist) {
                $sitemap_artists->add(
                    Url::create($artist->getUrl())
                        ->setLastModificationDate(now())
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(0.7)
                );
            }
        });
        $sitemap_artists->writeToFile(public_path('sitemap/artists-sitemap.xml'));
        $this->add_styles('sitemap/artists-sitemap.xml');
        $sitemap_index->add('sitemap/artists-sitemap.xml');

        // Publishers sitemap
        $sitemap_publishers = Sitemap::create();
        Publisher::chunkById(100, function ($publishers) use ($sitemap_publishers) {
            foreach ($publishers as $publisher) {
                $sitemap_publishers->add(
                    Url::create($publisher->getUrl())
                        ->setLastModificationDate(now())
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                        ->setPriority(0.7)
                );
            }
        });
        $sitemap_publishers->writeToFile(public_path('sitemap/publishers-sitemap.xml'));
        $this->add_styles('sitemap/publishers-sitemap.xml');
        $sitemap_index->add('sitemap/publishers-sitemap.xml');

        // Manga sitemap
        $chunk = 0;
        Manga::chunkById(200, function ($mangas) use ($sitemap_index, &$chunk) {
            $chunk++;
            $sitemap_mangas = Sitemap::create();
            foreach ($mangas as $manga) {
                $sitemap_mangas->add(
                    Url::create($manga->getUrl())
                        ->setLastModificationDate($manga->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                        ->setPriority(0.8)
                );
            }
            $sitemap_mangas->writeToFile(public_path("sitemap/manga-sitemap{$chunk}.xml"));
            $this->add_styles("sitemap/manga-sitemap{$chunk}.xml");
            $sitemap_index->add("sitemap/manga-sitemap{$chunk}.xml");
        });

        // Chapters sitemap
        $chunk = 0;
        Chapter::with('manga')->chunkById(500, function ($chapters) use ($sitemap_index, &$chunk) {
            $chunk++;
            $sitemap_chapters = Sitemap::create();
            foreach ($chapters as $chapter) {
                // Only include published chapters
                if ($chapter->published_at && $chapter->published_at <= now()) {
                    $sitemap_chapters->add(
                        Url::create($chapter->getUrl())
                            ->setLastModificationDate($chapter->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                            ->setPriority(0.6)
                    );
                }
            }
            if ($sitemap_chapters->getTags()->count() > 0) {
                $sitemap_chapters->writeToFile(public_path("sitemap/chapters-sitemap{$chunk}.xml"));
                $this->add_styles("sitemap/chapters-sitemap{$chunk}.xml");
                $sitemap_index->add("sitemap/chapters-sitemap{$chunk}.xml");
            }
        });

        $sitemap_index->writeToFile(public_path('sitemap.xml'));
        $this->add_styles("sitemap.xml");

        Alert::success("Đã tạo thành công sitemap cho manga tại thư mục public")->flash();

        return back();
    }
}
