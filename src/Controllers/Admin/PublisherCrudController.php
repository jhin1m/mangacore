<?php

namespace Ophim\Core\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Ophim\Core\Models\Publisher;

/**
 * Class PublisherCrudController
 * @package Ophim\Core\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class PublisherCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(Publisher::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/publisher');
        CRUD::setEntityNameStrings('nhà xuất bản', 'nhà xuất bản');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('name')->label('Tên');
        CRUD::column('slug')->label('Slug');
        CRUD::addColumn([
            'name' => 'mangas_count',
            'label' => 'Số manga',
            'type' => 'closure',
            'function' => function ($entry) {
                return $entry->mangas()->count();
            }
        ]);
        CRUD::column('created_at')->label('Ngày tạo');
        CRUD::column('updated_at')->label('Cập nhật');
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'name' => 'required|min:2|max:255',
            'slug' => 'nullable|min:2|max:255|unique:publishers,slug'
        ]);

        CRUD::field('name')->label('Tên nhà xuất bản');
        CRUD::field('slug')->label('Slug')->hint('Để trống để tự động tạo từ tên');
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
        
        CRUD::setValidation([
            'name' => 'required|min:2|max:255',
            'slug' => 'nullable|min:2|max:255|unique:publishers,slug,' . CRUD::getCurrentEntryId()
        ]);
    }
}