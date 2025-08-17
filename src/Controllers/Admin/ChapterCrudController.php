<?php

namespace Ophim\Core\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Page;
use Ophim\Core\Models\Manga;
use Ophim\Core\Requests\ChapterRequest;
use Ophim\Core\Services\ImageProcessor;
use ZipArchive;
use Carbon\Carbon;

/**
 * Class ChapterCrudController
 * @package Ophim\Core\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ChapterCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as backpackStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as backpackUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
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
        CRUD::setModel(\Ophim\Core\Models\Chapter::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/chapter');
        CRUD::setEntityNameStrings('Chapter', 'chapters');
        
        // Add custom buttons
        $this->crud->addButtonFromView('line', 'chapter_actions', 'ophim::crud.buttons.chapter_actions', 'end');
        $this->crud->addButtonFromView('top', 'batch_upload', 'ophim::crud.buttons.batch_upload', 'end');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->authorize('browse', Chapter::class);

        // Optimize queries with eager loading
        $this->crud->query = $this->crud->query->with([
            'manga:id,title,slug,type,status',
            'volume:id,volume_number,title'
        ]);

        $this->crud->enableExportButtons();

        // Add filters (temporarily simplified to avoid view issues)
        $this->crud->addFilter([
            'name' => 'manga_id',
            'type' => 'select2',
            'label' => 'Manga'
        ], function () {
            return Manga::all()->pluck('title', 'id')->toArray();
        }, function ($value) {
            $this->crud->addClause('where', 'manga_id', $value);
        });

        // Temporarily disable dropdown and date_range filters until views are published
        // Uncomment after running: php artisan vendor:publish --tag=cms_menu_content
        /*
        $this->crud->addFilter([
            'name' => 'is_premium',
            'type' => 'dropdown',
            'label' => 'Premium Status'
        ], [
            0 => 'Free',
            1 => 'Premium'
        ], function ($value) {
            $this->crud->addClause('where', 'is_premium', $value);
        });

        $this->crud->addFilter([
            'name' => 'published_at',
            'type' => 'date_range',
            'label' => 'Published Date'
        ], false, function ($value) {
            $dates = json_decode($value);
            $this->crud->addClause('where', 'published_at', '>=', $dates->from);
            $this->crud->addClause('where', 'published_at', '<=', $dates->to . ' 23:59:59');
        });
        */

        // Columns
        CRUD::addColumn([
            'name' => 'manga',
            'label' => 'Manga',
            'type' => 'relationship',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('manga', function ($manga) use ($searchTerm) {
                    $manga->where('title', 'like', '%' . $searchTerm . '%')
                        ->orWhere('original_title', 'like', '%' . $searchTerm . '%');
                });
            }
        ]);

        CRUD::addColumn([
            'name' => 'chapter_number',
            'label' => 'Chapter #',
            'type' => 'number',
            'decimals' => 1
        ]);

        CRUD::addColumn([
            'name' => 'title',
            'label' => 'Title',
            'type' => 'text',
            'limit' => 50
        ]);

        CRUD::addColumn([
            'name' => 'page_count',
            'label' => 'Pages',
            'type' => 'number'
        ]);

        CRUD::addColumn([
            'name' => 'view_count',
            'label' => 'Views',
            'type' => 'number'
        ]);

        CRUD::addColumn([
            'name' => 'is_premium',
            'label' => 'Premium',
            'type' => 'boolean'
        ]);

        CRUD::addColumn([
            'name' => 'published_at',
            'label' => 'Published',
            'type' => 'datetime'
        ]);

        CRUD::addColumn([
            'name' => 'updated_at',
            'label' => 'Updated',
            'type' => 'datetime'
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        $this->authorize('create', Chapter::class);

        CRUD::setValidation(ChapterRequest::class);

        // Basic Information Tab
        CRUD::addField([
            'name' => 'manga_id',
            'label' => 'Manga',
            'type' => 'select_manga',
            'tab' => 'Basic Info'
        ]);

        CRUD::addField([
            'name' => 'chapter_number',
            'label' => 'Chapter Number',
            'type' => 'number',
            'attributes' => [
                'step' => '0.1',
                'min' => '0'
            ],
            'tab' => 'Basic Info'
        ]);

        CRUD::addField([
            'name' => 'title',
            'label' => 'Chapter Title',
            'type' => 'text',
            'hint' => 'Leave empty for auto-generated title',
            'tab' => 'Basic Info'
        ]);

        CRUD::addField([
            'name' => 'volume_id',
            'label' => 'Volume',
            'type' => 'select2_from_ajax',
            'entity' => 'volume',
            'attribute' => 'title',
            'data_source' => url('admin/chapter/ajax-volume-options'),
            'placeholder' => 'Select volume (optional)',
            'minimum_input_length' => 0,
            'dependencies' => ['manga_id'],
            'tab' => 'Basic Info'
        ]);

        // Publishing Tab
        CRUD::addField([
            'name' => 'published_at',
            'label' => 'Publish Date',
            'type' => 'datetime_picker',
            'datetime_picker_options' => [
                'format' => 'YYYY-MM-DD HH:mm:ss',
                'language' => 'en'
            ],
            'tab' => 'Publishing'
        ]);

        CRUD::addField([
            'name' => 'is_premium',
            'label' => 'Premium Chapter',
            'type' => 'checkbox',
            'tab' => 'Publishing'
        ]);

        // Pages Tab
        CRUD::addField([
            'name' => 'pages_upload',
            'label' => 'Upload Pages',
            'type' => 'upload_multiple',
            'upload' => true,
            'disk' => 'public',
            'hint' => 'Upload individual page images',
            'tab' => 'Pages'
        ]);

        CRUD::addField([
            'name' => 'zip_upload',
            'label' => 'Upload ZIP File',
            'type' => 'upload',
            'upload' => true,
            'disk' => 'public',
            'hint' => 'Upload a ZIP file containing all chapter pages',
            'tab' => 'Pages'
        ]);

        // Add custom CSS/JS for enhanced functionality
        CRUD::addField([
            'name' => 'pages_manager',
            'type' => 'custom_html',
            'value' => '<div id="pages-manager" style="display:none;">
                <h5>Page Management</h5>
                <div id="pages-list"></div>
                <button type="button" class="btn btn-success" id="add-page-btn">Add Page</button>
            </div>',
            'tab' => 'Pages'
        ]);
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
    }

    /**
     * Store operation with custom handling for pages and ZIP uploads
     */
    public function store()
    {
        $this->crud->hasAccessOrFail('create');
        
        $request = $this->crud->validateRequest();
        
        // Handle the main chapter creation
        $chapter = $this->crud->create($this->crud->getStrippedSaveRequest());
        
        // Handle page uploads
        $this->handlePageUploads($chapter, $request);
        
        // Update page count
        $this->updateChapterPageCount($chapter);
        
        \Alert::success(trans('backpack::crud.insert_success'))->flash();
        
        return $this->crud->performSaveAction($chapter->getKey());
    }

    /**
     * Update operation with custom handling for pages
     */
    public function update()
    {
        $this->crud->hasAccessOrFail('update');
        
        $request = $this->crud->validateRequest();
        $chapter = $this->crud->update($this->crud->getCurrentEntryId(), $this->crud->getStrippedSaveRequest());
        
        // Handle page uploads
        $this->handlePageUploads($chapter, $request);
        
        // Update page count
        $this->updateChapterPageCount($chapter);
        
        \Alert::success(trans('backpack::crud.update_success'))->flash();
        
        return $this->crud->performSaveAction($chapter->getKey());
    }

    /**
     * Handle page uploads from individual files or ZIP
     */
    protected function handlePageUploads($chapter, $request)
    {
        $imageProcessor = new ImageProcessor();
        
        // Handle ZIP upload
        if ($request->hasFile('zip_upload')) {
            $this->processZipUpload($chapter, $request->file('zip_upload'), $imageProcessor);
        }
        
        // Handle individual page uploads
        if ($request->hasFile('pages_upload')) {
            $this->processIndividualUploads($chapter, $request->file('pages_upload'), $imageProcessor);
        }
    }

    /**
     * Process ZIP file upload and extract pages
     */
    protected function processZipUpload($chapter, $zipFile, $imageProcessor)
    {
        $zip = new ZipArchive();
        $tempPath = $zipFile->getPathname();
        
        if ($zip->open($tempPath) === TRUE) {
            $extractPath = storage_path('app/temp/chapter_' . $chapter->id);
            
            // Create extraction directory
            if (!file_exists($extractPath)) {
                mkdir($extractPath, 0755, true);
            }
            
            // Extract ZIP contents
            $zip->extractTo($extractPath);
            $zip->close();
            
            // Get all image files from extracted content
            $imageFiles = $this->getImageFilesFromDirectory($extractPath);
            
            // Sort files naturally (handles numeric ordering)
            natsort($imageFiles);
            
            // Process each image
            $pageNumber = $chapter->pages()->count() + 1;
            
            foreach ($imageFiles as $imagePath) {
                $this->createPageFromFile($chapter, $imagePath, $pageNumber, $imageProcessor);
                $pageNumber++;
            }
            
            // Clean up temporary files
            $this->cleanupDirectory($extractPath);
            
        } else {
            throw new \Exception('Failed to extract ZIP file');
        }
    }

    /**
     * Process individual file uploads
     */
    protected function processIndividualUploads($chapter, $files, $imageProcessor)
    {
        $pageNumber = $chapter->pages()->count() + 1;
        
        foreach ($files as $file) {
            $this->createPageFromUploadedFile($chapter, $file, $pageNumber, $imageProcessor);
            $pageNumber++;
        }
    }

    /**
     * Get all image files from directory recursively
     */
    protected function getImageFilesFromDirectory($directory)
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $imageFiles = [];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, $imageExtensions)) {
                    $imageFiles[] = $file->getPathname();
                }
            }
        }
        
        return $imageFiles;
    }

    /**
     * Create page from extracted file
     */
    protected function createPageFromFile($chapter, $filePath, $pageNumber, $imageProcessor)
    {
        // Generate storage path
        $filename = 'chapters/' . $chapter->id . '/page_' . str_pad($pageNumber, 3, '0', STR_PAD_LEFT) . '.' . pathinfo($filePath, PATHINFO_EXTENSION);
        $storagePath = 'public/' . $filename;
        
        // Move file to storage
        Storage::put($storagePath, file_get_contents($filePath));
        
        // Process and optimize image
        $optimizedPath = $imageProcessor->optimizeImage($storagePath);
        
        // Create page record
        Page::create([
            'chapter_id' => $chapter->id,
            'page_number' => $pageNumber,
            'image_url' => Storage::url($optimizedPath)
        ]);
    }

    /**
     * Create page from uploaded file
     */
    protected function createPageFromUploadedFile($chapter, $file, $pageNumber, $imageProcessor)
    {
        // Generate storage path
        $filename = 'chapters/' . $chapter->id . '/page_' . str_pad($pageNumber, 3, '0', STR_PAD_LEFT) . '.' . $file->getClientOriginalExtension();
        $storagePath = $file->storeAs('public', $filename);
        
        // Process and optimize image
        $optimizedPath = $imageProcessor->optimizeImage($storagePath);
        
        // Create page record
        Page::create([
            'chapter_id' => $chapter->id,
            'page_number' => $pageNumber,
            'image_url' => Storage::url($optimizedPath)
        ]);
    }

    /**
     * Update chapter page count
     */
    protected function updateChapterPageCount($chapter)
    {
        $pageCount = $chapter->pages()->count();
        $chapter->update(['page_count' => $pageCount]);
    }

    /**
     * Clean up temporary directory
     */
    protected function cleanupDirectory($directory)
    {
        if (is_dir($directory)) {
            $files = array_diff(scandir($directory), array('.', '..'));
            foreach ($files as $file) {
                $path = $directory . '/' . $file;
                is_dir($path) ? $this->cleanupDirectory($path) : unlink($path);
            }
            rmdir($directory);
        }
    }

    /**
     * Batch upload endpoint for AJAX requests
     */
    public function batchUpload(Request $request)
    {
        $this->crud->hasAccessOrFail('create');
        
        $validator = Validator::make($request->all(), [
            'manga_id' => 'required|exists:mangas,id',
            'chapters' => 'required|array',
            'chapters.*.chapter_number' => 'required|numeric',
            'chapters.*.title' => 'nullable|string',
            'chapters.*.zip_file' => 'required|file|mimes:zip'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $results = [];
        $imageProcessor = new ImageProcessor();
        
        foreach ($request->input('chapters') as $index => $chapterData) {
            try {
                // Create chapter
                $chapter = Chapter::create([
                    'manga_id' => $request->input('manga_id'),
                    'chapter_number' => $chapterData['chapter_number'],
                    'title' => $chapterData['title'] ?? null,
                    'published_at' => now()
                ]);
                
                // Process ZIP file
                $zipFile = $request->file("chapters.{$index}.zip_file");
                $this->processZipUpload($chapter, $zipFile, $imageProcessor);
                
                // Update page count
                $this->updateChapterPageCount($chapter);
                
                $results[] = [
                    'success' => true,
                    'chapter_id' => $chapter->id,
                    'chapter_number' => $chapter->chapter_number,
                    'page_count' => $chapter->page_count
                ];
                
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'chapter_number' => $chapterData['chapter_number']
                ];
            }
        }
        
        return response()->json(['results' => $results]);
    }

    /**
     * Reorder pages endpoint for drag-and-drop functionality
     */
    public function reorderPages(Request $request)
    {
        $this->crud->hasAccessOrFail('update');
        
        $validator = Validator::make($request->all(), [
            'chapter_id' => 'required|exists:chapters,id',
            'page_order' => 'required|array',
            'page_order.*' => 'exists:pages,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $chapter = Chapter::findOrFail($request->input('chapter_id'));
        $this->authorize('update', $chapter);
        
        $pageOrder = $request->input('page_order');
        
        foreach ($pageOrder as $index => $pageId) {
            Page::where('id', $pageId)
                ->where('chapter_id', $chapter->id)
                ->update(['page_number' => $index + 1]);
        }
        
        return response()->json(['success' => true]);
    }

    /**
     * Delete page endpoint
     */
    public function deletePage(Request $request, $pageId)
    {
        $page = Page::findOrFail($pageId);
        $chapter = $page->chapter;
        
        $this->authorize('update', $chapter);
        
        // Delete image file
        if ($page->image_url && !filter_var($page->image_url, FILTER_VALIDATE_URL)) {
            Storage::delete('public' . $page->image_url);
        }
        
        // Delete page record
        $page->delete();
        
        // Reorder remaining pages
        $remainingPages = $chapter->pages()->orderBy('page_number')->get();
        foreach ($remainingPages as $index => $remainingPage) {
            $remainingPage->update(['page_number' => $index + 1]);
        }
        
        // Update chapter page count
        $this->updateChapterPageCount($chapter);
        
        return response()->json(['success' => true]);
    }

    /**
     * Optimize chapter images endpoint
     */
    public function optimizeImages(Request $request, $chapterId)
    {
        $chapter = Chapter::findOrFail($chapterId);
        $this->authorize('update', $chapter);
        
        $imageProcessor = new ImageProcessor();
        $optimizedCount = 0;
        
        foreach ($chapter->pages as $page) {
            try {
                if (!filter_var($page->image_url, FILTER_VALIDATE_URL)) {
                    $storagePath = 'public' . $page->image_url;
                    $optimizedPath = $imageProcessor->optimizeImage($storagePath);
                    
                    if ($optimizedPath !== $storagePath) {
                        $page->update(['image_url' => Storage::url($optimizedPath)]);
                        $optimizedCount++;
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue with other images
                \Log::error("Failed to optimize page {$page->id}: " . $e->getMessage());
            }
        }
        
        return response()->json([
            'success' => true,
            'optimized_count' => $optimizedCount,
            'total_pages' => $chapter->pages->count()
        ]);
    }

    /**
     * AJAX endpoint for volume options based on selected manga
     */
    public function ajaxVolumeOptions(Request $request)
    {
        $mangaId = $request->get('manga_id');
        
        if (!$mangaId) {
            return response()->json([]);
        }
        
        $volumes = \Ophim\Core\Models\Volume::where('manga_id', $mangaId)
            ->orderBy('volume_number')
            ->get(['id', 'volume_number', 'title']);
        
        return response()->json($volumes->map(function ($volume) {
            return [
                'id' => $volume->id,
                'text' => "Volume {$volume->volume_number}" . ($volume->title ? ": {$volume->title}" : '')
            ];
        }));
    }

    /**
     * Pages manager view for editing chapters
     */
    public function pagesManager($id)
    {
        $chapter = Chapter::with(['pages' => function($query) {
            $query->orderBy('page_number');
        }])->findOrFail($id);
        
        $this->authorize('update', $chapter);
        
        return view('ophim::chapters.pages_manager', compact('chapter'));
    }

    /**
     * Upload pages to existing chapter
     */
    public function uploadPages(Request $request, $id)
    {
        $chapter = Chapter::findOrFail($id);
        $this->authorize('update', $chapter);
        
        $this->handlePageUploads($chapter, $request);
        $this->updateChapterPageCount($chapter);
        
        return response()->json([
            'success' => true,
            'message' => 'Pages uploaded successfully',
            'page_count' => $chapter->page_count
        ]);
    }

    /**
     * Duplicate chapter
     */
    public function duplicate($id)
    {
        $originalChapter = Chapter::with('pages')->findOrFail($id);
        $this->authorize('create', Chapter::class);
        
        // Create new chapter
        $newChapter = $originalChapter->replicate();
        $newChapter->chapter_number = $originalChapter->chapter_number + 0.1; // Increment by 0.1
        $newChapter->title = $originalChapter->title . ' (Copy)';
        $newChapter->published_at = null;
        $newChapter->save();
        
        // Copy pages
        foreach ($originalChapter->pages as $page) {
            $newPage = $page->replicate();
            $newPage->chapter_id = $newChapter->id;
            $newPage->save();
        }
        
        $this->updateChapterPageCount($newChapter);
        
        \Alert::success("Chapter duplicated successfully!")->flash();
        
        return redirect()->to($this->crud->route);
    }

    /**
     * Schedule chapter publishing
     */
    public function schedulePublishing(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'chapter_ids' => 'required|array',
            'chapter_ids.*' => 'exists:chapters,id',
            'published_at' => 'required|date|after:now'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $publishedAt = Carbon::parse($request->input('published_at'));
        $chapterIds = $request->input('chapter_ids');
        
        foreach ($chapterIds as $chapterId) {
            $chapter = Chapter::findOrFail($chapterId);
            $this->authorize('update', $chapter);
            
            $chapter->update(['published_at' => $publishedAt]);
        }
        
        return response()->json([
            'success' => true,
            'scheduled_count' => count($chapterIds),
            'publish_date' => $publishedAt->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Bulk delete operation
     */
    public function bulkDelete()
    {
        $this->crud->hasAccessOrFail('bulkDelete');
        $entries = request()->input('entries', []);
        $deletedEntries = [];

        foreach ($entries as $key => $id) {
            if ($entry = $this->crud->model->find($id)) {
                $this->authorize('delete', $entry);
                
                // Delete associated pages and images
                foreach ($entry->pages as $page) {
                    if ($page->image_url && !filter_var($page->image_url, FILTER_VALIDATE_URL)) {
                        Storage::delete('public' . $page->image_url);
                    }
                    $page->delete();
                }
                
                $deletedEntries[] = $entry->delete();
            }
        }

        return $deletedEntries;
    }
}