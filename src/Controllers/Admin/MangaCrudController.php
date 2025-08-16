<?php

namespace Ophim\Core\Controllers\Admin;

use Ophim\Core\Requests\MangaRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Ophim\Core\Models\Author;
use Ophim\Core\Models\Artist;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Origin;
use Ophim\Core\Models\Publisher;
use Ophim\Core\Models\Category;
use Ophim\Core\Models\Tag;

/**
 * Class MangaCrudController
 * @package Ophim\Core\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class MangaCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as backpackStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as backpackUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation {
        destroy as traitDestroy;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    use \Ophim\Core\Traits\Operations\BulkDeleteOperation {
        bulkDelete as traitBulkDelete;
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\Ophim\Core\Models\Manga::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/manga');
        CRUD::setEntityNameStrings('manga', 'mangas');
        CRUD::setCreateView('ophim::manga.create');
        CRUD::setUpdateView('ophim::manga.edit');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->authorize('browse', Manga::class);

        // Optimize queries with eager loading
        $this->crud->query = $this->crud->query->with([
            'authors:id,name,slug',
            'artists:id,name,slug', 
            'categories:id,name,slug',
            'tags:id,name,slug',
            'origins:id,name,slug'
        ]);

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number','tab'=>'Thông tin manga']);
         */

        // Manga-specific filters
        $this->crud->addFilter([
            'name'  => 'status',
            'type'  => 'select2',
            'label' => 'Tình trạng'
        ], function () {
            return [
                'ongoing' => 'Đang cập nhật',
                'completed' => 'Hoàn thành',
                'hiatus' => 'Tạm dừng',
                'cancelled' => 'Đã hủy'
            ];
        }, function ($val) {
            $this->crud->addClause('where', 'status', $val);
        });

        $this->crud->addFilter([
            'name'  => 'type',
            'type'  => 'select2',
            'label' => 'Loại manga'
        ], function () {
            return [
                'manga' => 'Manga',
                'manhwa' => 'Manhwa',
                'manhua' => 'Manhua',
                'webtoon' => 'Webtoon'
            ];
        }, function ($val) {
            $this->crud->addClause('where', 'type', $val);
        });

        $this->crud->addFilter([
            'name'  => 'demographic',
            'type'  => 'select2',
            'label' => 'Đối tượng'
        ], function () {
            return [
                'shounen' => 'Shounen',
                'seinen' => 'Seinen',
                'josei' => 'Josei',
                'shoujo' => 'Shoujo',
                'kodomomuke' => 'Kodomomuke',
                'general' => 'Tổng hợp'
            ];
        }, function ($val) {
            $this->crud->addClause('where', 'demographic', $val);
        });

        $this->crud->addFilter([
            'name'  => 'author_id',
            'type'  => 'select2',
            'label' => 'Tác giả'
        ], function () {
            return Author::all()->pluck('name', 'id')->toArray();
        }, function ($value) {
            $this->crud->query = $this->crud->query->whereHas('authors', function ($query) use ($value) {
                $query->where('id', $value);
            });
        });

        $this->crud->addFilter([
            'name'  => 'artist_id',
            'type'  => 'select2',
            'label' => 'Họa sĩ'
        ], function () {
            return Artist::all()->pluck('name', 'id')->toArray();
        }, function ($value) {
            $this->crud->query = $this->crud->query->whereHas('artists', function ($query) use ($value) {
                $query->where('id', $value);
            });
        });

        $this->crud->addFilter([
            'name'  => 'category_id',
            'type'  => 'select2',
            'label' => 'Thể loại'
        ], function () {
            return Category::all()->pluck('name', 'id')->toArray();
        }, function ($value) {
            $this->crud->query = $this->crud->query->whereHas('categories', function ($query) use ($value) {
                $query->where('id', $value);
            });
        });

        $this->crud->addFilter([
            'name'  => 'origin_id',
            'type'  => 'select2',
            'label' => 'Xuất xứ'
        ], function () {
            return Origin::all()->pluck('name', 'id')->toArray();
        }, function ($value) {
            $this->crud->query = $this->crud->query->whereHas('origins', function ($query) use ($value) {
                $query->where('id', $value);
            });
        });

        $this->crud->addFilter([
            'name'  => 'other',
            'type'  => 'select2',
            'label' => 'Thông tin'
        ], function () {
            return [
                'cover_image-' => 'Thiếu ảnh bìa',
                'banner_image-' => 'Thiếu ảnh banner',
                'description-' => 'Thiếu mô tả',
                'is_adult_content-1' => 'Nội dung người lớn',
            ];
        }, function ($values) {
            $value = explode("-", $values);
            $field = $value[0];
            $val = $value[1];
            if ($field === 'is_adult_content') {
                $this->crud->query->where($field, (bool)$val);
            } else {
                $this->crud->query->where($field, '')->orWhere($field, NULL);
            }
        });

        $this->crud->addFilter(
            [
                'type'  => 'simple',
                'name'  => 'is_recommended',
                'label' => 'Đề cử'
            ],
            false,
            function () {
                $this->crud->addClause('where', 'is_recommended', true);
            }
        );

        $this->crud->addFilter(
            [
                'type'  => 'simple',
                'name'  => 'is_completed',
                'label' => 'Đã hoàn thành'
            ],
            false,
            function () {
                $this->crud->addClause('where', 'is_completed', true);
            }
        );

        CRUD::addButtonFromModelFunction('line', 'open_view', 'openView', 'beginning');

        // Manga-specific columns
        CRUD::addColumn([
            'name' => 'title',
            'original_title' => 'original_title',
            'publication_year' => 'publication_year',
            'status' => 'status',
            'type' => 'type',
            'demographic' => 'demographic',
            'total_chapters' => 'total_chapters',
            'label' => 'Thông tin manga',
            'type' => 'view',
            'view' => 'ophim::manga.columns.column_manga_info',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->where('title', 'like', '%' . $searchTerm . '%')
                      ->orWhere('original_title', 'like', '%' . $searchTerm . '%')
                      ->orWhere('other_name', 'like', '%' . $searchTerm . '%');
            }
        ]);

        CRUD::addColumn([
            'name' => 'cover_image', 
            'label' => 'Ảnh bìa', 
            'type' => 'image',
            'height' => '100px',
            'width'  => '68px',
        ]);
        
        CRUD::addColumn(['name' => 'categories', 'label' => 'Thể loại', 'type' => 'relationship']);
        CRUD::addColumn(['name' => 'origins', 'label' => 'Xuất xứ', 'type' => 'relationship']);
        CRUD::addColumn(['name' => 'updated_at', 'label' => 'Cập nhật lúc', 'type' => 'datetime', 'format' => 'DD/MM/YYYY HH:mm:ss']);
        CRUD::addColumn(['name' => 'view_count', 'label' => 'Lượt xem', 'type' => 'number']);
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        $this->authorize('create', Manga::class);

        CRUD::setValidation(MangaRequest::class);

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number']));
         */

        // Basic Information Tab
        CRUD::addField(['name' => 'title', 'label' => 'Tên manga', 'type' => 'text', 'wrapperAttributes' => [
            'class' => 'form-group col-md-6'
        ], 'attributes' => ['placeholder' => 'Tên manga'], 'tab' => 'Thông tin cơ bản']);
        
        CRUD::addField(['name' => 'original_title', 'label' => 'Tên gốc', 'type' => 'text', 'wrapperAttributes' => [
            'class' => 'form-group col-md-6'
        ], 'attributes' => ['placeholder' => 'Tên gốc (tiếng Nhật, Hàn, Trung...)'], 'tab' => 'Thông tin cơ bản']);
        
        CRUD::addField(['name' => 'slug', 'label' => 'Đường dẫn tĩnh', 'type' => 'text', 'tab' => 'Thông tin cơ bản']);
        
        CRUD::addField(['name' => 'other_name', 'label' => 'Tên khác', 'type' => 'text', 'attributes' => ['placeholder' => 'Các tên khác, cách nhau bởi dấu phẩy'], 'tab' => 'Thông tin cơ bản']);

        // Image uploads
        CRUD::addField([
            'name' => 'cover_image', 
            'label' => 'Ảnh bìa', 
            'type' => 'ckfinder', 
            'preview' => ['width' => 'auto', 'height' => '340px'], 
            'tab' => 'Thông tin cơ bản'
        ]);
        
        CRUD::addField([
            'name' => 'banner_image', 
            'label' => 'Ảnh banner', 
            'type' => 'ckfinder', 
            'preview' => ['width' => 'auto', 'height' => '200px'], 
            'tab' => 'Thông tin cơ bản'
        ]);

        CRUD::addField(['name' => 'description', 'label' => 'Mô tả', 'type' => 'summernote', 'tab' => 'Thông tin cơ bản']);

        // Publication Information
        CRUD::addField(['name' => 'publication_year', 'label' => 'Năm xuất bản', 'type' => 'number', 'wrapperAttributes' => [
            'class' => 'form-group col-md-4'
        ], 'attributes' => ['min' => 1900, 'max' => date('Y') + 1], 'tab' => 'Thông tin xuất bản']);

        CRUD::addField(['name' => 'total_chapters', 'label' => 'Tổng số chương', 'type' => 'number', 'wrapperAttributes' => [
            'class' => 'form-group col-md-4'
        ], 'attributes' => ['min' => 0, 'placeholder' => 'Để trống nếu chưa biết'], 'tab' => 'Thông tin xuất bản']);

        CRUD::addField(['name' => 'total_volumes', 'label' => 'Tổng số tập', 'type' => 'number', 'wrapperAttributes' => [
            'class' => 'form-group col-md-4'
        ], 'attributes' => ['min' => 0, 'placeholder' => 'Để trống nếu không có'], 'tab' => 'Thông tin xuất bản']);

        CRUD::addField(['name' => 'rating', 'label' => 'Đánh giá', 'type' => 'number', 'attributes' => ['step' => '0.1', 'min' => 0, 'max' => 10], 'wrapperAttributes' => [
            'class' => 'form-group col-md-6'
        ], 'tab' => 'Thông tin xuất bản']);

        // Classification Tab
        CRUD::addField(['name' => 'type', 'label' => 'Loại manga', 'type' => 'radio', 'options' => [
            'manga' => 'Manga (Nhật Bản)', 
            'manhwa' => 'Manhwa (Hàn Quốc)', 
            'manhua' => 'Manhua (Trung Quốc)',
            'webtoon' => 'Webtoon'
        ], 'tab' => 'Phân loại']);
        
        CRUD::addField(['name' => 'status', 'label' => 'Tình trạng', 'type' => 'radio', 'options' => [
            'ongoing' => 'Đang cập nhật', 
            'completed' => 'Hoàn thành', 
            'hiatus' => 'Tạm dừng',
            'cancelled' => 'Đã hủy'
        ], 'tab' => 'Phân loại']);
        
        CRUD::addField(['name' => 'demographic', 'label' => 'Đối tượng', 'type' => 'select_from_array', 'options' => [
            'shounen' => 'Shounen',
            'seinen' => 'Seinen', 
            'josei' => 'Josei',
            'shoujo' => 'Shoujo',
            'kodomomuke' => 'Kodomomuke',
            'general' => 'Tổng hợp'
        ], 'default' => 'general', 'tab' => 'Phân loại']);
        
        CRUD::addField(['name' => 'reading_direction', 'label' => 'Hướng đọc', 'type' => 'radio', 'options' => [
            'ltr' => 'Trái sang phải', 
            'rtl' => 'Phải sang trái', 
            'vertical' => 'Dọc (Webtoon)'
        ], 'default' => 'ltr', 'tab' => 'Phân loại']);

        CRUD::addField(['name' => 'categories', 'label' => 'Thể loại', 'type' => 'checklist', 'tab' => 'Phân loại']);
        CRUD::addField(['name' => 'origins', 'label' => 'Xuất xứ', 'type' => 'checklist', 'tab' => 'Phân loại']);
        CRUD::addField(['name' => 'authors', 'label' => 'Tác giả', 'type' => 'select2_relationship_tags', 'tab' => 'Phân loại']);
        CRUD::addField(['name' => 'artists', 'label' => 'Họa sĩ', 'type' => 'select2_relationship_tags', 'tab' => 'Phân loại']);
        CRUD::addField(['name' => 'publishers', 'label' => 'Nhà xuất bản', 'type' => 'select2_relationship_tags', 'tab' => 'Phân loại']);
        CRUD::addField(['name' => 'tags', 'label' => 'Tags', 'type' => 'select2_relationship_tags', 'tab' => 'Phân loại']);

        // Chapters Tab - placeholder for future chapter management
        CRUD::addField([
            'name' => 'chapters_info',
            'type' => 'custom_html',
            'value' => '<div class="alert alert-info">Quản lý chương sẽ được thêm vào sau khi tạo manga.</div>',
            'tab' => 'Danh sách chương'
        ]);

        // Advanced Options Tab
        CRUD::addField(['name' => 'is_completed', 'label' => 'Đã hoàn thành', 'type' => 'boolean', 'tab' => 'Tùy chọn nâng cao']);
        CRUD::addField(['name' => 'is_recommended', 'label' => 'Đề cử', 'type' => 'boolean', 'tab' => 'Tùy chọn nâng cao']);
        CRUD::addField(['name' => 'is_adult_content', 'label' => 'Nội dung người lớn', 'type' => 'boolean', 'tab' => 'Tùy chọn nâng cao']);
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->authorize('update', $this->crud->getEntryWithLocale($this->crud->getCurrentEntryId()));

        $this->setupCreateOperation();
        CRUD::addField(['name' => 'timestamps', 'label' => 'Cập nhật thời gian', 'type' => 'checkbox', 'tab' => 'Tùy chọn nâng cao']);
    }

    public function store(Request $request)
    {
        $this->getTaxonomies($request);

        return $this->backpackStore();
    }

    public function update(Request $request)
    {
        $this->getTaxonomies($request);

        return $this->backpackUpdate();
    }

    protected function getTaxonomies(Request $request)
    {
        $authors = request('authors', []);
        $artists = request('artists', []);
        $publishers = request('publishers', []);
        $tags = request('tags', []);

        $author_ids = [];
        foreach ($authors as $author) {
            $author_ids[] = Author::firstOrCreate([
                'name_md5' => md5($author)
            ], [
                'name' => $author
            ])->id;
        }

        $artist_ids = [];
        foreach ($artists as $artist) {
            $artist_ids[] = Artist::firstOrCreate([
                'name_md5' => md5($artist)
            ], [
                'name' => $artist
            ])->id;
        }

        $publisher_ids = [];
        foreach ($publishers as $publisher) {
            $publisher_ids[] = Publisher::firstOrCreate([
                'name_md5' => md5($publisher)
            ], [
                'name' => $publisher
            ])->id;
        }

        $tag_ids = [];
        foreach ($tags as $tag) {
            $tag_ids[] = Tag::firstOrCreate([
                'name_md5' => md5($tag)
            ], [
                'name' => $tag
            ])->id;
        }

        $request['authors'] = $author_ids;
        $request['artists'] = $artist_ids;
        $request['publishers'] = $publisher_ids;
        $request['tags'] = $tag_ids;
    }

    public function deleteImage($manga)
    {
        // Delete images
        if ($manga->cover_image && !filter_var($manga->cover_image, FILTER_VALIDATE_URL) && file_exists(public_path($manga->cover_image))) {
            unlink(public_path($manga->cover_image));
        }
        if ($manga->banner_image && !filter_var($manga->banner_image, FILTER_VALIDATE_URL) && file_exists(public_path($manga->banner_image))) {
            unlink(public_path($manga->banner_image));
        }
        return true;
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');
        $manga = Manga::find($id);

        $this->deleteImage($manga);

        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;

        $res = $this->crud->delete($id);
        return $res;
    }

    public function bulkDelete()
    {
        $this->crud->hasAccessOrFail('bulkDelete');
        $entries = request()->input('entries', []);
        $deletedEntries = [];

        foreach ($entries as $key => $id) {
            if ($entry = $this->crud->model->find($id)) {
                $this->deleteImage($entry);
                $deletedEntries[] = $entry->delete();
            }
        }

        return $deletedEntries;
    }
}