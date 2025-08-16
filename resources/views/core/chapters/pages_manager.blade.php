{{-- Pages Manager for Chapter CRUD --}}
<div class="form-group col-sm-12" element="div" bp-field-wrapper="true" bp-field-name="existing_pages" bp-field-type="view">
    <label>Current Pages</label>
    
    <div id="pages-manager" class="card">
        <div class="card-header">
            <h5 class="mb-0">
                Chapter Pages Management
                <span class="badge badge-info ml-2" id="page-count">{{ $entry->pages->count() }} pages</span>
            </h5>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-primary" id="optimize-images">
                    <i class="fa fa-compress"></i> Optimize All Images
                </button>
                <button type="button" class="btn btn-sm btn-success" id="add-pages">
                    <i class="fa fa-plus"></i> Add More Pages
                </button>
            </div>
        </div>
        
        <div class="card-body">
            @if($entry->pages->count() > 0)
                <div class="row" id="pages-container">
                    @foreach($entry->pages->sortBy('page_number') as $page)
                        <div class="col-md-3 col-sm-4 col-6 mb-3 page-item" data-page-id="{{ $page->id }}" data-page-number="{{ $page->page_number }}">
                            <div class="card page-card">
                                <div class="card-header p-2">
                                    <small class="text-muted">
                                        <i class="fa fa-grip-vertical drag-handle" style="cursor: move;"></i>
                                        Page {{ $page->page_number }}
                                    </small>
                                    <button type="button" class="btn btn-sm btn-danger float-right delete-page" data-page-id="{{ $page->id }}">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                                <div class="card-body p-2">
                                    <div class="page-preview">
                                        <img src="{{ $page->getThumbnailUrl() }}" 
                                             alt="Page {{ $page->page_number }}" 
                                             class="img-fluid page-thumbnail"
                                             style="max-height: 200px; width: 100%; object-fit: cover;">
                                    </div>
                                    <div class="page-info mt-2">
                                        <small class="text-muted d-block">
                                            <i class="fa fa-file-image"></i> 
                                            {{ pathinfo($page->image_url, PATHINFO_EXTENSION) }}
                                        </small>
                                        @if($page->getHumanFileSize())
                                            <small class="text-muted d-block">
                                                <i class="fa fa-weight"></i> 
                                                {{ $page->getHumanFileSize() }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-3">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        <strong>Tips:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Drag and drop pages to reorder them</li>
                            <li>Click the trash icon to delete a page</li>
                            <li>Use "Optimize All Images" to compress and improve loading times</li>
                            <li>Add more pages using the "Add More Pages" button</li>
                        </ul>
                    </div>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fa fa-images fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No pages uploaded yet</h5>
                    <p class="text-muted">Upload pages using the "Pages" tab or use the batch upload feature.</p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Add Pages Modal --}}
<div class="modal fade" id="add-pages-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add More Pages</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="add-pages-form" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="chapter_id" value="{{ $entry->id }}">
                    
                    <div class="form-group">
                        <label>Upload Individual Images</label>
                        <input type="file" name="pages[]" class="form-control-file" multiple accept="image/*">
                        <small class="form-text text-muted">Select multiple image files to upload as pages.</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Or Upload ZIP File</label>
                        <input type="file" name="zip_file" class="form-control-file" accept=".zip">
                        <small class="form-text text-muted">Upload a ZIP file containing all page images.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="upload-pages">Upload Pages</button>
            </div>
        </div>
    </div>
</div>

{{-- Styles --}}
<style>
.page-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.page-card:hover {
    border-color: #007bff;
    box-shadow: 0 4px 8px rgba(0,123,255,0.25);
}

.page-item.ui-sortable-helper {
    transform: rotate(5deg);
    box-shadow: 0 8px 16px rgba(0,0,0,0.3);
}

.page-thumbnail {
    border-radius: 4px;
    cursor: pointer;
}

.drag-handle {
    cursor: move !important;
}

.delete-page {
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.delete-page:hover {
    opacity: 1;
}

#pages-container.sorting .page-card {
    cursor: move;
}

.upload-progress {
    display: none;
}

.upload-progress .progress {
    height: 20px;
}
</style>

{{-- JavaScript --}}
<script>
$(document).ready(function() {
    // Make pages sortable
    $("#pages-container").sortable({
        handle: '.drag-handle',
        placeholder: 'col-md-3 col-sm-4 col-6 mb-3',
        start: function(e, ui) {
            ui.placeholder.html('<div class="card" style="height: ' + ui.item.height() + 'px; border: 2px dashed #ccc;"></div>');
            $('#pages-container').addClass('sorting');
        },
        stop: function(e, ui) {
            $('#pages-container').removeClass('sorting');
            updatePageOrder();
        }
    });

    // Update page order after drag and drop
    function updatePageOrder() {
        var pageOrder = [];
        $('.page-item').each(function(index) {
            pageOrder.push($(this).data('page-id'));
        });

        $.ajax({
            url: '{{ backpack_url('chapter/reorder-pages') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                chapter_id: {{ $entry->id }},
                page_order: pageOrder
            },
            success: function(response) {
                if (response.success) {
                    // Update page numbers in UI
                    $('.page-item').each(function(index) {
                        $(this).attr('data-page-number', index + 1);
                        $(this).find('.card-header small').html(
                            '<i class="fa fa-grip-vertical drag-handle" style="cursor: move;"></i> Page ' + (index + 1)
                        );
                    });
                    
                    new Noty({
                        type: 'success',
                        text: 'Pages reordered successfully!'
                    }).show();
                }
            },
            error: function() {
                new Noty({
                    type: 'error',
                    text: 'Failed to reorder pages. Please try again.'
                }).show();
            }
        });
    }

    // Delete page
    $(document).on('click', '.delete-page', function() {
        var pageId = $(this).data('page-id');
        var pageItem = $(this).closest('.page-item');
        var pageNumber = pageItem.data('page-number');

        if (confirm('Are you sure you want to delete Page ' + pageNumber + '? This action cannot be undone.')) {
            $.ajax({
                url: '{{ backpack_url('chapter/delete-page') }}/' + pageId,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        pageItem.fadeOut(300, function() {
                            $(this).remove();
                            updatePageCount();
                            // Renumber remaining pages
                            $('.page-item').each(function(index) {
                                $(this).attr('data-page-number', index + 1);
                                $(this).find('.card-header small').html(
                                    '<i class="fa fa-grip-vertical drag-handle" style="cursor: move;"></i> Page ' + (index + 1)
                                );
                            });
                        });
                        
                        new Noty({
                            type: 'success',
                            text: 'Page deleted successfully!'
                        }).show();
                    }
                },
                error: function() {
                    new Noty({
                        type: 'error',
                        text: 'Failed to delete page. Please try again.'
                    }).show();
                }
            });
        }
    });

    // Optimize images
    $('#optimize-images').click(function() {
        var btn = $(this);
        var originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Optimizing...');

        $.ajax({
            url: '{{ backpack_url('chapter/optimize-images') }}/{{ $entry->id }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    new Noty({
                        type: 'success',
                        text: 'Optimized ' + response.optimized_count + ' out of ' + response.total_pages + ' images!'
                    }).show();
                    
                    // Reload page thumbnails
                    $('.page-thumbnail').each(function() {
                        var src = $(this).attr('src');
                        $(this).attr('src', src + '?t=' + Date.now());
                    });
                }
            },
            error: function() {
                new Noty({
                    type: 'error',
                    text: 'Failed to optimize images. Please try again.'
                }).show();
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Add pages modal
    $('#add-pages').click(function() {
        $('#add-pages-modal').modal('show');
    });

    // Upload pages
    $('#upload-pages').click(function() {
        var formData = new FormData($('#add-pages-form')[0]);
        var btn = $(this);
        var originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Uploading...');

        $.ajax({
            url: '{{ backpack_url('chapter') }}/{{ $entry->id }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#add-pages-modal').modal('hide');
                new Noty({
                    type: 'success',
                    text: 'Pages uploaded successfully! Refreshing page...'
                }).show();
                
                // Refresh the page to show new pages
                setTimeout(function() {
                    location.reload();
                }, 1500);
            },
            error: function(xhr) {
                var errorMsg = 'Failed to upload pages.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMsg += ' ' + Object.values(xhr.responseJSON.errors).flat().join(' ');
                }
                
                new Noty({
                    type: 'error',
                    text: errorMsg
                }).show();
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Preview page image on click
    $(document).on('click', '.page-thumbnail', function() {
        var imgSrc = $(this).attr('src').replace('_thumb', '_optimized');
        var pageNumber = $(this).closest('.page-item').data('page-number');
        
        var modal = $('<div class="modal fade" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Page ' + pageNumber + ' Preview</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body text-center"><img src="' + imgSrc + '" class="img-fluid" style="max-height: 80vh;"></div></div></div></div>');
        
        $('body').append(modal);
        modal.modal('show');
        modal.on('hidden.bs.modal', function() {
            modal.remove();
        });
    });

    // Update page count
    function updatePageCount() {
        var count = $('.page-item').length;
        $('#page-count').text(count + ' pages');
    }
});
</script>