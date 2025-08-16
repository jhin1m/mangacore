/**
 * Manga Reader JavaScript Interface
 * Handles multiple reading modes, navigation, and user interactions
 */

class MangaReader {
    constructor(options = {}) {
        this.manga = options.manga || window.readerData?.manga;
        this.chapter = options.chapter || window.readerData?.chapter;
        this.navigation = options.navigation || window.readerData?.navigation;
        this.settings = options.settings || window.readerData?.settings || {};
        this.apiUrls = options.apiUrls || window.readerData?.apiUrls || {};
        
        // Reader state
        this.currentPage = 1;
        this.totalPages = this.chapter?.page_count || 0;
        this.readingMode = this.settings.reading_mode || 'single';
        this.imageQuality = this.settings.image_quality || 'medium';
        this.isFullscreen = false;
        this.uiVisible = true;
        this.preloadBuffer = 3;
        
        // Touch/swipe handling
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.swipeThreshold = 50;
        
        // Auto-save progress timer
        this.progressSaveTimer = null;
        
        // Bookmarks
        this.bookmarks = [];
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupReadingMode();
        this.loadUserProgress();
        this.preloadPages();
        this.setupKeyboardShortcuts();
        this.setupTouchGestures();
        this.loadBookmarks();
        
        // Add ARIA labels for accessibility
        this.setupAccessibility();
        
        console.log('Manga Reader initialized', {
            manga: this.manga?.title,
            chapter: this.chapter?.title,
            mode: this.readingMode
        });
    }

    bindEvents() {
        // Navigation buttons
        $('.prev-page').on('click', () => this.previousPage());
        $('.next-page').on('click', () => this.nextPage());
        
        // Reading mode selection
        $('.reading-mode-option').on('click', (e) => {
            e.preventDefault();
            const mode = $(e.target).data('mode');
            this.changeReadingMode(mode);
        });
        
        // Image quality selection
        $('.quality-option').on('click', (e) => {
            e.preventDefault();
            const quality = $(e.target).data('quality');
            this.changeImageQuality(quality);
        });
        
        // Fullscreen toggle
        $('.fullscreen-toggle').on('click', () => this.toggleFullscreen());
        
        // Page click navigation (for single/double page modes)
        $(document).on('click', '.manga-page', (e) => {
            if (this.readingMode === 'single' || this.readingMode === 'double') {
                const clickX = e.pageX;
                const pageWidth = $(e.target).width();
                const clickPosition = clickX / pageWidth;
                
                // Navigate based on reading direction and click position
                if (this.manga.reading_direction === 'rtl') {
                    clickPosition < 0.5 ? this.nextPage() : this.previousPage();
                } else {
                    clickPosition > 0.5 ? this.nextPage() : this.previousPage();
                }
            }
        });
        
        // Retry loading on error
        $('.retry-loading').on('click', () => this.retryLoading());
        
        // Bookmark functionality
        $('.bookmark-toggle').on('click', () => this.toggleBookmark());
        $('.bookmark-item').on('click', (e) => {
            e.preventDefault();
            const page = $(e.target).data('page');
            this.jumpToPage(page);
        });
        
        // Window resize handling
        $(window).on('resize', () => this.handleResize());
        
        // Scroll handling for vertical mode
        $(window).on('scroll', () => this.handleScroll());
    }

    setupKeyboardShortcuts() {
        $(document).on('keydown', (e) => {
            // Ignore if user is typing in an input
            if ($(e.target).is('input, textarea')) return;
            
            switch(e.key) {
                case 'ArrowLeft':
                    e.preventDefault();
                    this.manga.reading_direction === 'rtl' ? this.nextPage() : this.previousPage();
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    this.manga.reading_direction === 'rtl' ? this.previousPage() : this.nextPage();
                    break;
                case ' ':
                    e.preventDefault();
                    e.shiftKey ? this.previousPage() : this.nextPage();
                    break;
                case 'f':
                case 'F':
                    e.preventDefault();
                    this.toggleFullscreen();
                    break;
                case 'h':
                case 'H':
                    e.preventDefault();
                    this.toggleUI();
                    break;
                case 'b':
                case 'B':
                    e.preventDefault();
                    this.toggleBookmark();
                    break;
                case '?':
                    e.preventDefault();
                    $('#shortcutsModal').modal('show');
                    break;
                case 'Escape':
                    if (this.isFullscreen) {
                        this.exitFullscreen();
                    }
                    break;
            }
        });
    }

    setupTouchGestures() {
        const readerContent = $('.reader-content')[0];
        
        if (readerContent) {
            let touchStartTime = 0;
            
            readerContent.addEventListener('touchstart', (e) => {
                this.touchStartX = e.touches[0].clientX;
                this.touchStartY = e.touches[0].clientY;
                touchStartTime = Date.now();
            }, { passive: true });
            
            readerContent.addEventListener('touchend', (e) => {
                if (!this.touchStartX || !this.touchStartY) return;
                
                const touchEndX = e.changedTouches[0].clientX;
                const touchEndY = e.changedTouches[0].clientY;
                const touchEndTime = Date.now();
                
                const deltaX = this.touchStartX - touchEndX;
                const deltaY = this.touchStartY - touchEndY;
                const touchDuration = touchEndTime - touchStartTime;
                
                // Handle tap to toggle UI (short touch with minimal movement)
                if (Math.abs(deltaX) < 10 && Math.abs(deltaY) < 10 && touchDuration < 300) {
                    this.toggleUI();
                    return;
                }
                
                // Handle swipe gestures
                if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > this.swipeThreshold) {
                    // Prevent accidental swipes during scrolling
                    if (touchDuration < 1000) {
                        if (deltaX > 0) {
                            // Swipe left
                            this.manga.reading_direction === 'rtl' ? this.previousPage() : this.nextPage();
                        } else {
                            // Swipe right
                            this.manga.reading_direction === 'rtl' ? this.nextPage() : this.previousPage();
                        }
                    }
                }
                
                // Handle vertical swipe for vertical mode
                if (this.readingMode === 'vertical' && Math.abs(deltaY) > Math.abs(deltaX) && Math.abs(deltaY) > this.swipeThreshold) {
                    if (deltaY > 0) {
                        // Swipe up - next page
                        this.scrollToNextPage();
                    } else {
                        // Swipe down - previous page
                        this.scrollToPreviousPage();
                    }
                }
                
                this.touchStartX = 0;
                this.touchStartY = 0;
            }, { passive: true });
            
            // Handle pinch-to-zoom for better mobile experience
            readerContent.addEventListener('touchmove', (e) => {
                if (e.touches.length === 2) {
                    // Allow native pinch-to-zoom
                    e.preventDefault();
                }
            }, { passive: false });
        }
    }

    setupReadingMode() {
        // Hide all reading mode containers
        $('.reader-pages, .reader-vertical').hide();
        
        // Show appropriate container based on reading mode
        switch(this.readingMode) {
            case 'vertical':
                $('.reader-vertical').show();
                this.setupVerticalMode();
                break;
            case 'horizontal':
                $('.reader-pages').show().addClass('horizontal-mode');
                this.setupHorizontalMode();
                break;
            case 'double':
                $('.reader-pages').show().addClass('double-page-mode').removeClass('single-page-mode');
                this.setupDoublePageMode();
                break;
            case 'single':
            default:
                $('.reader-pages').show().addClass('single-page-mode').removeClass('double-page-mode horizontal-mode');
                this.setupSinglePageMode();
                break;
        }
        
        // Update UI to reflect current mode
        $('.reading-mode-option').removeClass('active');
        $(`.reading-mode-option[data-mode="${this.readingMode}"]`).addClass('active');
    }

    setupSinglePageMode() {
        $('.page-wrapper').hide();
        this.showCurrentPage();
    }

    setupDoublePageMode() {
        $('.page-wrapper').hide();
        this.showCurrentDoublePage();
    }

    setupVerticalMode() {
        $('.vertical-page').show();
        this.updateVerticalProgress();
    }

    setupHorizontalMode() {
        $('.page-wrapper').show();
        $('.reader-pages').css({
            'display': 'flex',
            'overflow-x': 'auto',
            'scroll-snap-type': 'x mandatory'
        });
        $('.page-wrapper').css('scroll-snap-align', 'start');
    }

    showCurrentPage() {
        $('.page-wrapper').hide();
        $(`.page-wrapper[data-page="${this.currentPage}"]`).show();
        this.updatePageInfo();
        this.updateProgress();
    }

    showCurrentDoublePage() {
        $('.page-wrapper').hide();
        
        // Show current page
        $(`.page-wrapper[data-page="${this.currentPage}"]`).show();
        
        // Show next page if available and reading direction allows
        if (this.currentPage < this.totalPages) {
            const nextPage = this.currentPage + 1;
            $(`.page-wrapper[data-page="${nextPage}"]`).show();
        }
        
        this.updatePageInfo();
        this.updateProgress();
    }

    nextPage() {
        if (this.readingMode === 'vertical') {
            this.scrollToNextPage();
            return;
        }
        
        const increment = this.readingMode === 'double' ? 2 : 1;
        
        if (this.currentPage + increment <= this.totalPages) {
            this.currentPage += increment;
            this.showCurrentPage();
            this.preloadPages();
            this.saveProgress();
        } else {
            // End of chapter, navigate to next chapter
            this.navigateToNextChapter();
        }
    }

    previousPage() {
        if (this.readingMode === 'vertical') {
            this.scrollToPreviousPage();
            return;
        }
        
        const decrement = this.readingMode === 'double' ? 2 : 1;
        
        if (this.currentPage - decrement >= 1) {
            this.currentPage -= decrement;
            this.showCurrentPage();
        } else {
            // Beginning of chapter, navigate to previous chapter
            this.navigateToPreviousChapter();
        }
    }

    jumpToPage(pageNumber) {
        if (pageNumber >= 1 && pageNumber <= this.totalPages) {
            this.currentPage = pageNumber;
            
            if (this.readingMode === 'vertical') {
                this.scrollToPage(pageNumber);
            } else {
                this.showCurrentPage();
            }
            
            this.saveProgress();
        }
    }

    scrollToNextPage() {
        const currentPageElement = $(`.vertical-page[data-page="${this.currentPage + 1}"]`);
        if (currentPageElement.length) {
            currentPageElement[0].scrollIntoView({ behavior: 'smooth' });
            this.currentPage++;
            this.updatePageInfo();
            this.saveProgress();
        }
    }

    scrollToPreviousPage() {
        if (this.currentPage > 1) {
            const previousPageElement = $(`.vertical-page[data-page="${this.currentPage - 1}"]`);
            if (previousPageElement.length) {
                previousPageElement[0].scrollIntoView({ behavior: 'smooth' });
                this.currentPage--;
                this.updatePageInfo();
            }
        }
    }

    scrollToPage(pageNumber) {
        const pageElement = $(`.vertical-page[data-page="${pageNumber}"]`);
        if (pageElement.length) {
            pageElement[0].scrollIntoView({ behavior: 'smooth' });
            this.currentPage = pageNumber;
            this.updatePageInfo();
        }
    }

    handleScroll() {
        if (this.readingMode === 'vertical') {
            this.updateVerticalProgress();
        }
    }

    updateVerticalProgress() {
        const scrollTop = $(window).scrollTop();
        const windowHeight = $(window).height();
        const documentHeight = $(document).height();
        
        // Calculate which page is currently in view
        $('.vertical-page').each((index, element) => {
            const $element = $(element);
            const elementTop = $element.offset().top;
            const elementHeight = $element.height();
            
            if (elementTop <= scrollTop + windowHeight / 2 && 
                elementTop + elementHeight > scrollTop + windowHeight / 2) {
                const pageNumber = parseInt($element.data('page'));
                if (pageNumber !== this.currentPage) {
                    this.currentPage = pageNumber;
                    this.updatePageInfo();
                    this.debouncedSaveProgress();
                }
            }
        });
    }

    changeReadingMode(mode) {
        if (mode === this.readingMode) return;
        
        this.readingMode = mode;
        this.setupReadingMode();
        this.saveSettings();
        
        // Show notification
        this.showNotification(`Switched to ${mode} mode`);
    }

    changeImageQuality(quality) {
        if (quality === this.imageQuality) return;
        
        this.imageQuality = quality;
        this.updateImageQuality();
        this.saveSettings();
        
        // Show notification
        this.showNotification(`Image quality set to ${quality}`);
    }

    updateImageQuality() {
        $('.manga-page, .manga-page-vertical').each((index, img) => {
            const $img = $(img);
            const newSrc = $img.data(this.imageQuality);
            if (newSrc && newSrc !== $img.attr('src')) {
                $img.attr('src', newSrc);
            }
        });
        
        // Update quality option UI
        $('.quality-option').removeClass('active');
        $(`.quality-option[data-quality="${this.imageQuality}"]`).addClass('active');
    }

    preloadPages() {
        const startPage = Math.max(1, this.currentPage - 1);
        const endPage = Math.min(this.totalPages, this.currentPage + this.preloadBuffer);
        
        // Preload images in the buffer range
        for (let page = startPage; page <= endPage; page++) {
            const pageElement = $(`.page-wrapper[data-page="${page}"] img, .vertical-page[data-page="${page}"] img`);
            if (pageElement.length) {
                const img = new Image();
                const imageUrl = pageElement.data(this.imageQuality) || pageElement.attr('src');
                
                // Add error handling for preloaded images
                img.onload = () => {
                    console.log(`Preloaded page ${page}`);
                };
                img.onerror = () => {
                    console.warn(`Failed to preload page ${page}, trying fallback quality`);
                    // Try with lower quality as fallback
                    if (this.imageQuality !== 'low') {
                        const fallbackImg = new Image();
                        fallbackImg.src = pageElement.data('low') || pageElement.attr('src');
                    }
                };
                
                img.src = imageUrl;
            }
        }
        
        // API call for additional preloading if available
        if (this.apiUrls.preloadPages) {
            $.ajax({
                url: this.apiUrls.preloadPages,
                method: 'POST',
                data: {
                    chapter_id: this.chapter.id,
                    current_page: this.currentPage,
                    buffer_size: this.preloadBuffer,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: (response) => {
                    if (response.success && response.pages) {
                        response.pages.forEach(page => {
                            const img = new Image();
                            img.onload = () => console.log(`API preloaded page ${page.page_number}`);
                            img.onerror = () => console.warn(`API preload failed for page ${page.page_number}`);
                            img.src = page.image_url;
                        });
                    }
                },
                error: (xhr) => {
                    console.warn('Preload API request failed:', xhr.responseJSON);
                }
            });
        }
    }

    saveProgress() {
        if (!this.apiUrls.saveProgress) return;
        
        // Clear existing timer
        if (this.progressSaveTimer) {
            clearTimeout(this.progressSaveTimer);
        }
        
        // Save progress after a short delay to avoid too many requests
        this.progressSaveTimer = setTimeout(() => {
            $.ajax({
                url: this.apiUrls.saveProgress,
                method: 'POST',
                data: {
                    manga_id: this.manga.id,
                    chapter_id: this.chapter.id,
                    page_number: this.currentPage,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: (response) => {
                    if (response.success) {
                        console.log('Progress saved:', response.data);
                    }
                },
                error: (xhr) => {
                    this.handleNetworkError(xhr, 'save reading progress');
                }
            });
        }, 1000);
    }

    debouncedSaveProgress() {
        // Debounced version for scroll events
        if (this.progressSaveTimer) {
            clearTimeout(this.progressSaveTimer);
        }
        
        this.progressSaveTimer = setTimeout(() => {
            this.saveProgress();
        }, 2000);
    }

    saveSettings() {
        if (!this.apiUrls.updateSettings) return;
        
        const settings = {
            reading_mode: this.readingMode,
            image_quality: this.imageQuality,
            manga_id: this.manga.id,
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        $.ajax({
            url: this.apiUrls.updateSettings,
            method: 'POST',
            data: settings,
            success: (response) => {
                if (response.success) {
                    console.log('Settings saved:', response.settings);
                }
            }
        });
    }

    loadUserProgress() {
        const progress = window.readerData?.readingProgress;
        if (progress) {
            let lastPage = 1;
            
            if (typeof progress === 'object' && progress.page_number) {
                lastPage = progress.page_number;
            } else if (typeof progress === 'object' && progress.chapter_id === this.chapter.id) {
                lastPage = progress.page_number || 1;
            }
            
            // Ask user if they want to continue from last position
            if (lastPage > 1 && lastPage <= this.totalPages) {
                const continueReading = confirm(`Continue reading from page ${lastPage}?`);
                if (continueReading) {
                    this.jumpToPage(lastPage);
                }
            }
        }
    }

    updatePageInfo() {
        $('.current-page').text(this.currentPage);
        $('.total-pages').text(this.totalPages);
        
        // Update navigation buttons
        $('.prev-page').prop('disabled', this.currentPage <= 1);
        $('.next-page').prop('disabled', this.currentPage >= this.totalPages);
        
        // Update progress bar
        const progress = (this.currentPage / this.totalPages) * 100;
        $('.progress-bar').css('width', `${progress}%`);
        
        // Update bookmark UI for current page
        this.updateBookmarkUI();
    }

    updateProgress() {
        this.updatePageInfo();
    }

    navigateToNextChapter() {
        if (this.navigation.next_chapter) {
            const proceed = confirm('End of chapter. Go to next chapter?');
            if (proceed) {
                window.location.href = this.navigation.next_chapter.url;
            }
        } else {
            this.showNotification('You have reached the last available chapter.');
        }
    }

    navigateToPreviousChapter() {
        if (this.navigation.previous_chapter) {
            const proceed = confirm('Beginning of chapter. Go to previous chapter?');
            if (proceed) {
                window.location.href = this.navigation.previous_chapter.url;
            }
        } else {
            this.showNotification('This is the first chapter.');
        }
    }

    toggleFullscreen() {
        if (!this.isFullscreen) {
            this.enterFullscreen();
        } else {
            this.exitFullscreen();
        }
    }

    enterFullscreen() {
        const element = document.documentElement;
        
        if (element.requestFullscreen) {
            element.requestFullscreen();
        } else if (element.webkitRequestFullscreen) {
            element.webkitRequestFullscreen();
        } else if (element.msRequestFullscreen) {
            element.msRequestFullscreen();
        }
        
        this.isFullscreen = true;
        $('.fullscreen-toggle i').removeClass('fa-expand').addClass('fa-compress');
        $('body').addClass('reader-fullscreen');
    }

    exitFullscreen() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
        
        this.isFullscreen = false;
        $('.fullscreen-toggle i').removeClass('fa-compress').addClass('fa-expand');
        $('body').removeClass('reader-fullscreen');
    }

    toggleUI() {
        if (this.uiVisible) {
            $('.reader-header, .reader-footer').fadeOut();
            this.uiVisible = false;
        } else {
            $('.reader-header, .reader-footer').fadeIn();
            this.uiVisible = true;
        }
    }

    handleResize() {
        // Recalculate layout for current reading mode
        if (this.readingMode === 'vertical') {
            this.updateVerticalProgress();
        }
    }

    retryLoading() {
        $('.reader-error').hide();
        $('.reader-loading').show();
        
        // Reload chapter data
        if (this.apiUrls.chapterData) {
            $.ajax({
                url: this.apiUrls.chapterData.replace(':id', this.chapter.id),
                method: 'GET',
                success: (response) => {
                    if (response.chapter && response.pages) {
                        this.chapter = response.chapter;
                        this.totalPages = response.pages.length;
                        this.reloadPages(response.pages);
                        $('.reader-loading').hide();
                    }
                },
                error: (xhr) => {
                    $('.reader-loading').hide();
                    $('.reader-error').show();
                    this.handleNetworkError(xhr, 'load chapter data');
                }
            });
        }
    }

    reloadPages(pages) {
        // Rebuild page elements with new data
        const pageContainer = $('.page-container');
        const verticalContainer = $('.reader-vertical');
        
        pageContainer.empty();
        verticalContainer.empty();
        
        pages.forEach(page => {
            // Single/Double page mode
            const pageWrapper = $(`
                <div class="page-wrapper" data-page="${page.page_number}">
                    <img class="manga-page" 
                         src="${page.optimized_urls.medium}"
                         data-webp="${page.webp_url}"
                         data-low="${page.optimized_urls.low}"
                         data-medium="${page.optimized_urls.medium}"
                         data-high="${page.optimized_urls.high}"
                         alt="Page ${page.page_number}"
                         loading="lazy">
                    <div class="page-number">${page.page_number} / ${this.totalPages}</div>
                </div>
            `);
            pageContainer.append(pageWrapper);
            
            // Vertical mode
            const verticalPage = $(`
                <div class="vertical-page" data-page="${page.page_number}">
                    <img class="manga-page-vertical" 
                         src="${page.optimized_urls.medium}"
                         data-webp="${page.webp_url}"
                         alt="Page ${page.page_number}"
                         loading="lazy">
                </div>
            `);
            verticalContainer.append(verticalPage);
        });
        
        this.setupReadingMode();
    }

    toggleBookmark() {
        const currentBookmark = {
            manga_id: this.manga.id,
            chapter_id: this.chapter.id,
            page_number: this.currentPage,
            title: `${this.manga.title} - Chapter ${this.chapter.chapter_number} - Page ${this.currentPage}`,
            created_at: new Date().toISOString()
        };
        
        const existingIndex = this.bookmarks.findIndex(b => 
            b.manga_id === currentBookmark.manga_id && 
            b.chapter_id === currentBookmark.chapter_id && 
            b.page_number === currentBookmark.page_number
        );
        
        if (existingIndex >= 0) {
            // Remove bookmark
            this.bookmarks.splice(existingIndex, 1);
            this.showNotification('Bookmark removed', 'info');
            $('.bookmark-toggle').removeClass('active');
        } else {
            // Add bookmark
            this.bookmarks.push(currentBookmark);
            this.showNotification('Page bookmarked', 'success');
            $('.bookmark-toggle').addClass('active');
        }
        
        this.saveBookmarks();
        this.updateBookmarkUI();
    }

    loadBookmarks() {
        // Load from localStorage for guest users or API for logged-in users
        if (this.apiUrls.loadBookmarks) {
            $.ajax({
                url: this.apiUrls.loadBookmarks,
                method: 'GET',
                data: { manga_id: this.manga.id },
                success: (response) => {
                    if (response.success && response.bookmarks) {
                        this.bookmarks = response.bookmarks;
                        this.updateBookmarkUI();
                    }
                }
            });
        } else {
            // Load from localStorage for guest users
            const storageKey = `manga_bookmarks_${this.manga.id}`;
            const stored = localStorage.getItem(storageKey);
            if (stored) {
                try {
                    this.bookmarks = JSON.parse(stored);
                    this.updateBookmarkUI();
                } catch (e) {
                    console.error('Failed to parse bookmarks from localStorage:', e);
                    this.bookmarks = [];
                }
            }
        }
    }

    saveBookmarks() {
        if (this.apiUrls.saveBookmarks) {
            // Save to server for logged-in users
            $.ajax({
                url: this.apiUrls.saveBookmarks,
                method: 'POST',
                data: {
                    manga_id: this.manga.id,
                    bookmarks: this.bookmarks,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: (response) => {
                    if (response.success) {
                        console.log('Bookmarks saved to server');
                    }
                }
            });
        } else {
            // Save to localStorage for guest users
            const storageKey = `manga_bookmarks_${this.manga.id}`;
            try {
                localStorage.setItem(storageKey, JSON.stringify(this.bookmarks));
                console.log('Bookmarks saved to localStorage');
            } catch (e) {
                console.error('Failed to save bookmarks to localStorage:', e);
            }
        }
    }

    updateBookmarkUI() {
        // Update bookmark toggle button state
        const isBookmarked = this.bookmarks.some(b => 
            b.manga_id === this.manga.id && 
            b.chapter_id === this.chapter.id && 
            b.page_number === this.currentPage
        );
        
        if (isBookmarked) {
            $('.bookmark-toggle').addClass('active');
        } else {
            $('.bookmark-toggle').removeClass('active');
        }
        
        // Update bookmarks list in sidebar/dropdown
        const bookmarksList = $('.bookmarks-list');
        if (bookmarksList.length) {
            bookmarksList.empty();
            
            this.bookmarks.forEach(bookmark => {
                const bookmarkItem = $(`
                    <div class="bookmark-item" data-page="${bookmark.page_number}">
                        <div class="bookmark-title">${bookmark.title}</div>
                        <div class="bookmark-date">${new Date(bookmark.created_at).toLocaleDateString()}</div>
                        <button class="btn btn-sm btn-danger remove-bookmark" data-bookmark-id="${bookmark.id || bookmark.page_number}">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                `);
                bookmarksList.append(bookmarkItem);
            });
            
            // Bind remove bookmark events
            $('.remove-bookmark').on('click', (e) => {
                e.stopPropagation();
                const bookmarkId = $(e.target).closest('.remove-bookmark').data('bookmark-id');
                this.removeBookmark(bookmarkId);
            });
        }
    }

    removeBookmark(bookmarkId) {
        const index = this.bookmarks.findIndex(b => 
            (b.id && b.id === bookmarkId) || b.page_number === bookmarkId
        );
        
        if (index >= 0) {
            this.bookmarks.splice(index, 1);
            this.saveBookmarks();
            this.updateBookmarkUI();
            this.showNotification('Bookmark removed', 'info');
        }
    }

    setupAccessibility() {
        // Add ARIA labels and roles for screen readers
        $('.reader-content').attr({
            'role': 'main',
            'aria-label': `Reading ${this.manga?.title || 'manga'} chapter ${this.chapter?.chapter_number || ''}`
        });
        
        $('.manga-page, .manga-page-vertical').attr({
            'role': 'img',
            'tabindex': '0'
        }).each((index, img) => {
            const $img = $(img);
            const pageNum = $img.closest('[data-page]').data('page');
            $img.attr('aria-label', `Page ${pageNum} of ${this.totalPages}`);
        });
        
        // Navigation buttons
        $('.prev-page').attr('aria-label', 'Go to previous page');
        $('.next-page').attr('aria-label', 'Go to next page');
        $('.bookmark-toggle').attr('aria-label', 'Toggle bookmark for current page');
        $('.fullscreen-toggle').attr('aria-label', 'Toggle fullscreen mode');
        
        // Reading mode buttons
        $('.reading-mode-option').each((index, btn) => {
            const mode = $(btn).data('mode');
            $(btn).attr('aria-label', `Switch to ${mode} reading mode`);
        });
        
        // Add keyboard navigation for images
        $('.manga-page, .manga-page-vertical').on('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.nextPage();
            }
        });
    }

    // Enhanced error handling for network issues
    handleNetworkError(xhr, operation = 'operation') {
        let errorMessage = `Failed to ${operation}`;
        
        if (xhr.status === 0) {
            errorMessage = 'Network connection lost. Please check your internet connection.';
        } else if (xhr.status === 404) {
            errorMessage = 'Content not found. This chapter may have been removed.';
        } else if (xhr.status === 500) {
            errorMessage = 'Server error. Please try again later.';
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
        }
        
        this.showNotification(errorMessage, 'danger');
        console.error(`${operation} failed:`, xhr);
    }

    showNotification(message, type = 'info') {
        // Create and show a temporary notification
        const notification = $(`
            <div class="reader-notification alert alert-${type} alert-dismissible fade show">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `);
        
        $('body').append(notification);
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            notification.alert('close');
        }, 3000);
    }
}

// Initialize reader when document is ready
$(document).ready(function() {
    if (window.readerData && $('#manga-reader').length) {
        window.mangaReader = new MangaReader();
    }
});

// Handle fullscreen change events
$(document).on('fullscreenchange webkitfullscreenchange mozfullscreenchange MSFullscreenChange', function() {
    if (window.mangaReader) {
        const isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || 
                               document.mozFullScreenElement || document.msFullscreenElement);
        
        if (!isFullscreen && window.mangaReader.isFullscreen) {
            window.mangaReader.exitFullscreen();
        }
    }
});