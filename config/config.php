<?php

return [
    'version' => class_exists('\PackageVersions\Versions') 
        ? explode('@', \PackageVersions\Versions::getVersion('jhin1m/mangacore') ?? '1.0.0')[0] 
        : '1.0.0',
    
    // Legacy episode configuration (for backward compatibility)
    'episodes' => [
        'types' => [
            'embed' => 'Nhúng',
            'mp4' => 'MP4',
            'm3u8' => 'M3U8'
        ]
    ],
    
    // Manga-specific configuration
    'manga' => [
        'types' => [
            'manga' => 'Manga',
            'manhwa' => 'Manhwa',
            'manhua' => 'Manhua',
            'webtoon' => 'Webtoon'
        ],
        'statuses' => [
            'ongoing' => 'Đang tiến hành',
            'completed' => 'Hoàn thành',
            'hiatus' => 'Tạm ngưng',
            'cancelled' => 'Đã hủy'
        ],
        'demographics' => [
            'shounen' => 'Shounen',
            'seinen' => 'Seinen',
            'josei' => 'Josei',
            'shoujo' => 'Shoujo',
            'kodomomuke' => 'Kodomomuke',
            'general' => 'Tổng quát'
        ],
        'reading_directions' => [
            'ltr' => 'Trái sang phải',
            'rtl' => 'Phải sang trái',
            'vertical' => 'Dọc (Webtoon)'
        ],
        'reading_modes' => [
            'single' => 'Trang đơn',
            'double' => 'Trang đôi',
            'vertical' => 'Cuộn dọc',
            'horizontal' => 'Vuốt ngang'
        ]
    ],
    
    // Chapter configuration
    'chapters' => [
        'batch_upload' => [
            'max_files' => 200,
            'max_file_size' => 10 * 1024 * 1024, // 10MB per file
            'max_total_size' => 500 * 1024 * 1024, // 500MB total
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'],
            'zip_extraction' => [
                'max_files' => 500,
                'timeout' => 300, // 5 minutes
                'memory_limit' => '512M'
            ]
        ],
        'page_numbering' => [
            'start_from' => 1,
            'allow_decimal' => true, // For fractional chapters like 4.5
            'auto_sort' => true
        ]
    ],
    'ckfinder' => [
        'loadRoutes' => false,
        'backends' => [
            'name'         => 'default',
            'adapter'      => 'local',
            'baseUrl'      => '/storage/',
            'root'         => public_path('/storage/'),
            'chmodFiles'   => 0777,
            'chmodFolders' => 0755,
            'filesystemEncoding' => 'UTF-8'
        ]
    ],
    'image_processing' => [
        'default_quality' => 'medium',
        'generate_webp' => true,
        'generate_thumbnails' => true,
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'supported_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'],
        'quality_settings' => [
            'low' => 60,
            'medium' => 80,
            'high' => 95
        ],
        'dimensions' => [
            'page' => ['width' => 1600, 'height' => 2400],
            'thumbnail' => ['width' => 300, 'height' => 450],
            'cover' => ['width' => 800, 'height' => 1200],
            'banner' => ['width' => 1920, 'height' => 1080]
        ]
    ],
    'cdn' => [
        'enabled' => env('CDN_ENABLED', false),
        'disk' => env('CDN_DISK', 'public'),
        'base_url' => env('CDN_BASE_URL', ''),
        'supports_optimization' => env('CDN_SUPPORTS_OPTIMIZATION', false),
        'cache_purge_enabled' => env('CDN_CACHE_PURGE_ENABLED', false)
    ],
    
    // Reading progress configuration
    'reading_progress' => [
        'auto_save' => true,
        'save_interval' => 5, // seconds
        'guest_session_duration' => 30 * 24 * 60, // 30 days in minutes
        'cleanup_old_progress' => true,
        'cleanup_after_days' => 90
    ],
    
    // Cache configuration for manga content
    'cache' => [
        'manga_details' => [
            'ttl' => 3600, // 1 hour
            'tags' => ['manga']
        ],
        'chapter_pages' => [
            'ttl' => 7200, // 2 hours
            'tags' => ['chapters', 'pages']
        ],
        'reading_progress' => [
            'ttl' => 1800, // 30 minutes
            'tags' => ['progress']
        ],
        'manga_lists' => [
            'ttl' => 1800, // 30 minutes
            'tags' => ['manga', 'lists']
        ]
    ],
    
    // Reader configuration
    'reader' => [
        'preload_pages' => 3,
        'default_reading_mode' => 'single',
        'keyboard_shortcuts' => [
            'next_page' => ['ArrowRight', 'Space', 'KeyD'],
            'prev_page' => ['ArrowLeft', 'KeyA'],
            'next_chapter' => ['ArrowDown', 'KeyS'],
            'prev_chapter' => ['ArrowUp', 'KeyW'],
            'toggle_fullscreen' => ['KeyF'],
            'toggle_reading_mode' => ['KeyM']
        ],
        'touch_gestures' => [
            'swipe_threshold' => 50, // pixels
            'tap_zones' => [
                'prev' => 0.3, // left 30% of screen
                'next' => 0.7  // right 70% of screen
            ]
        ]
    ]
];
