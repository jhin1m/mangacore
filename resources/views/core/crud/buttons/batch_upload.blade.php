{{-- Batch Upload Button for Chapter CRUD --}}
@if ($crud->hasAccess('create'))
    <button type="button" 
            class="btn btn-primary" 
            data-toggle="modal" 
            data-target="#batch-upload-modal">
        <i class="fa fa-upload"></i> Batch Upload Chapters
    </button>

    {{-- Batch Upload Modal --}}
    <div class="modal fade" id="batch-upload-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-upload"></i> Batch Upload Chapters
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="batch-upload-form" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="batch-manga-id">Select Manga *</label>
                                <select name="manga_id" id="batch-manga-id" class="form-control" required>
                                    <option value="">Choose manga...</option>
                                    @foreach(\Ophim\Core\Models\Manga::orderBy('title')->get() as $manga)
                                        <option value="{{ $manga->id }}">{{ $manga->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-success btn-block" id="add-chapter-row">
                                    <i class="fa fa-plus"></i> Add Chapter
                                </button>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            <strong>Instructions:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Select the manga first, then add chapters one by one</li>
                                <li>Each chapter requires a chapter number and ZIP file containing page images</li>
                                <li>Images in ZIP files will be automatically ordered by filename</li>
                                <li>Chapter titles are optional - leave empty for default "Chapter X" format</li>
                            </ul>
                        </div>

                        <div id="chapters-container">
                            <!-- Chapter rows will be added here dynamically -->
                        </div>

                        <div class="text-center mt-3" id="no-chapters-message">
                            <p class="text-muted">
                                <i class="fa fa-arrow-up"></i> 
                                Click "Add Chapter" to start adding chapters for batch upload
                            </p>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="start-batch-upload" disabled>
                        <i class="fa fa-upload"></i> Start Upload
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Progress Modal --}}
    <div class="modal fade" id="upload-progress-modal" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa fa-spinner fa-spin"></i> Uploading Chapters
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="progress mb-3">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                    <div id="upload-status">
                        <p>Preparing upload...</p>
                    </div>
                    <div id="upload-results" style="display: none;">
                        <!-- Results will be shown here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="close-progress" disabled>Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        let chapterCount = 0;

        // Add chapter row
        $('#add-chapter-row').click(function() {
            if (!$('#batch-manga-id').val()) {
                new Noty({
                    type: 'warning',
                    text: 'Please select a manga first!'
                }).show();
                return;
            }

            chapterCount++;
            const chapterRow = `
                <div class="card mb-3 chapter-row" data-chapter="${chapterCount}">
                    <div class="card-header">
                        <h6 class="mb-0">
                            Chapter ${chapterCount}
                            <button type="button" class="btn btn-sm btn-danger float-right remove-chapter">
                                <i class="fa fa-trash"></i>
                            </button>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Chapter Number *</label>
                                <input type="number" 
                                       name="chapters[${chapterCount-1}][chapter_number]" 
                                       class="form-control chapter-number" 
                                       step="0.1" 
                                       min="0" 
                                       required>
                            </div>
                            <div class="col-md-4">
                                <label>Chapter Title (Optional)</label>
                                <input type="text" 
                                       name="chapters[${chapterCount-1}][title]" 
                                       class="form-control" 
                                       placeholder="Leave empty for default">
                            </div>
                            <div class="col-md-5">
                                <label>ZIP File *</label>
                                <input type="file" 
                                       name="chapters[${chapterCount-1}][zip_file]" 
                                       class="form-control-file chapter-zip" 
                                       accept=".zip" 
                                       required>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#chapters-container').append(chapterRow);
            $('#no-chapters-message').hide();
            updateUploadButton();
        });

        // Remove chapter row
        $(document).on('click', '.remove-chapter', function() {
            $(this).closest('.chapter-row').remove();
            if ($('.chapter-row').length === 0) {
                $('#no-chapters-message').show();
            }
            updateUploadButton();
        });

        // Update upload button state
        function updateUploadButton() {
            const hasChapters = $('.chapter-row').length > 0;
            const hasManga = $('#batch-manga-id').val() !== '';
            $('#start-batch-upload').prop('disabled', !hasChapters || !hasManga);
        }

        // Manga selection change
        $('#batch-manga-id').change(function() {
            updateUploadButton();
        });

        // Start batch upload
        $('#start-batch-upload').click(function() {
            const formData = new FormData($('#batch-upload-form')[0]);
            
            // Validate form
            let isValid = true;
            $('.chapter-number, .chapter-zip').each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (!isValid) {
                new Noty({
                    type: 'error',
                    text: 'Please fill in all required fields!'
                }).show();
                return;
            }

            // Hide batch modal and show progress modal
            $('#batch-upload-modal').modal('hide');
            $('#upload-progress-modal').modal('show');

            // Start upload
            $.ajax({
                url: '{{ backpack_url('chapter/batch-upload') }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = evt.loaded / evt.total * 100;
                            $('.progress-bar').css('width', percentComplete + '%');
                            $('#upload-status').html('<p>Uploading... ' + Math.round(percentComplete) + '%</p>');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    $('.progress-bar').css('width', '100%');
                    $('#upload-status').html('<p class="text-success"><i class="fa fa-check"></i> Upload completed!</p>');
                    
                    // Show results
                    let resultsHtml = '<h6>Upload Results:</h6><ul class="list-unstyled">';
                    response.results.forEach(function(result) {
                        if (result.success) {
                            resultsHtml += `<li class="text-success"><i class="fa fa-check"></i> Chapter ${result.chapter_number}: ${result.page_count} pages uploaded</li>`;
                        } else {
                            resultsHtml += `<li class="text-danger"><i class="fa fa-times"></i> Chapter ${result.chapter_number}: ${result.error}</li>`;
                        }
                    });
                    resultsHtml += '</ul>';
                    
                    $('#upload-results').html(resultsHtml).show();
                    $('#close-progress').prop('disabled', false);
                    
                    // Show success notification
                    const successCount = response.results.filter(r => r.success).length;
                    new Noty({
                        type: 'success',
                        text: `Successfully uploaded ${successCount} chapters!`
                    }).show();
                },
                error: function(xhr) {
                    $('.progress-bar').addClass('bg-danger');
                    $('#upload-status').html('<p class="text-danger"><i class="fa fa-times"></i> Upload failed!</p>');
                    
                    let errorMsg = 'An error occurred during upload.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    
                    $('#upload-results').html('<div class="alert alert-danger">' + errorMsg + '</div>').show();
                    $('#close-progress').prop('disabled', false);
                }
            });
        });

        // Close progress modal
        $('#close-progress').click(function() {
            $('#upload-progress-modal').modal('hide');
            // Reset forms
            $('#batch-upload-form')[0].reset();
            $('#chapters-container').empty();
            $('#no-chapters-message').show();
            chapterCount = 0;
            updateUploadButton();
            
            // Refresh the page to show new chapters
            location.reload();
        });

        // Reset modal when closed
        $('#batch-upload-modal').on('hidden.bs.modal', function() {
            if (!$('#upload-progress-modal').hasClass('show')) {
                $('#batch-upload-form')[0].reset();
                $('#chapters-container').empty();
                $('#no-chapters-message').show();
                chapterCount = 0;
                updateUploadButton();
            }
        });
    });
    </script>
@endif