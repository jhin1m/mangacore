@extends(backpack_view('blank'))

@php
    $widgets['before_content'] = [
        [
            'type' => 'alert',
            'class' => 'alert alert-dark mb-2 col-12',
            'heading' => 'MangaCore - Professional Manga Management System',
            'content' =>
                '
                Version: <span class="text-danger text-break">' .
                config('ophim.version') .
                '</span><br/>
                Homepage: <a href="https://ophimcms.com">OPhimCMS.Com</a><br/>
                Comprehensive manga content management system
            ',
            'close_button' => true, // show close button or not
        ],
    ];
@endphp

@section('content')
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/waypoints/2.0.3/waypoints.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Counter-Up/1.0.0/jquery.counterup.min.js"
        integrity="sha512-d8F1J2kyiRowBB/8/pAWsqUl0wSEOkG5KATkVV4slfblq9VRQ6MyDZVxWl2tWd+mPhuCbpTB4M7uU/x9FlgQ9Q=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        jQuery(document).ready(function($) {
            $('.counter').counterUp({
                delay: 10,
                time: 500
            });
        });
    </script>
    <style>
        .card-counter {
            box-shadow: 2px 2px 10px #DADADA;
            margin: 5px 0;
            padding: 20px 10px;
            background-color: #fff;
            height: 100px;
            border-radius: 5px;
            transition: .3s linear all;
        }

        .card-counter:hover {
            box-shadow: 4px 4px 20px #DADADA;
            transition: .3s linear all;
        }

        .card-counter.primary {
            background-color: #007bff;
            color: #FFF;
        }

        .card-counter.danger {
            background-color: #ef5350;
            color: #FFF;
        }

        .card-counter.success {
            background-color: #66bb6a;
            color: #FFF;
        }

        .card-counter.info {
            background-color: #26c6da;
            color: #FFF;
        }

        .card-counter.warning {
            background-color: #ff9800;
            color: #FFF;
        }

        .card-counter i {
            font-size: 5em;
            opacity: 0.2;
        }

        .card-counter .count-numbers {
            position: absolute;
            right: 35px;
            top: 20px;
            font-size: 32px;
            display: block;
        }

        .card-counter .count-name {
            position: absolute;
            right: 35px;
            top: 65px;
            font-style: italic;
            text-transform: capitalize;
            opacity: 0.5;
            display: block;
            font-size: 18px;
        }
    </style>
    <div class="row">
        <div class="col-md-2">
            <div class="card-counter primary">
                <i class="las la-book"></i>
                <span class="count-numbers counter">{{ $count_manga }}</span>
                <span class="count-name">Tổng số manga</span>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card-counter info">
                <i class="las la-bookmark"></i>
                <span class="count-numbers counter">{{ $count_chapters }}</span>
                <span class="count-name">Tổng số chapter</span>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card-counter success">
                <i class="las la-book-reader"></i>
                <span class="count-numbers counter">{{ $count_daily_readers }}</span>
                <span class="count-name">Độc giả hôm nay</span>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card-counter warning">
                <i class="las la-user"></i>
                <span class="count-numbers counter">{{ $count_users }}</span>
                <span class="count-name">Người dùng</span>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card-counter bg-primary">
                <i class="la la-paint-brush"></i>
                <span class="count-numbers counter">{{ $count_themes }}</span>
                <span class="count-name">Giao diện</span>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card-counter">
                <i class="las la-puzzle-piece"></i>
                <span class="count-numbers counter">{{ count(config('plugins', [])) }}</span>
                <span class="count-name">Plugins</span>
            </div>
        </div>
    </div>
    <!-- Charts Row -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Phân bố trạng thái Manga</h5>
                </div>
                <div class="card-body">
                    <canvas id="mangaStatusChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Hoạt động đọc 7 ngày qua</h5>
                </div>
                <div class="card-body">
                    <canvas id="readingActivityChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Popular Manga Chart -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Top 10 Manga phổ biến nhất</h5>
                </div>
                <div class="card-body">
                    <canvas id="popularMangaChart" width="400" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Tables Row -->
    <div class="row mt-4">
        <div class="p-3 col-md-4">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th colspan="2" scope="col">TOP NGÀY</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @foreach ($top_view_day as $manga)
                        <tr>
                            <td><a href="{{ $manga->getUrl() }}">{{ $manga->title }}</a></td>
                            <td class="text-right"><span class="badge badge-success"><i class="las la-eye"></i> {{ number_format($manga->view_day) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3 col-md-4">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th colspan="2" scope="col">TOP TUẦN</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @foreach ($top_view_week as $manga)
                        <tr>
                            <td><a href="{{ $manga->getUrl() }}">{{ $manga->title }}</a></td>
                            <td class="text-right"><span class="badge badge-success"><i class="las la-eye"></i> {{ number_format($manga->view_week) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3 col-md-4">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th colspan="2" scope="col">TOP THÁNG</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @foreach ($top_view_month as $manga)
                        <tr>
                            <td><a href="{{ $manga->getUrl() }}">{{ $manga->title }}</a></td>
                            <td class="text-right"><span class="badge badge-success"><i class="las la-eye"></i> {{ number_format($manga->view_month) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Manga Status Distribution Pie Chart
        const statusCtx = document.getElementById('mangaStatusChart').getContext('2d');
        const statusData = @json($manga_status_distribution);
        
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: [
                        '#28a745', // Completed - Green
                        '#007bff', // Ongoing - Blue  
                        '#ffc107', // Hiatus - Yellow
                        '#dc3545'  // Cancelled - Red
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Reading Activity Line Chart
        const activityCtx = document.getElementById('readingActivityChart').getContext('2d');
        const activityData = @json($reading_activity_data);
        
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: activityData.map(item => item.date),
                datasets: [{
                    label: 'Độc giả',
                    data: activityData.map(item => item.readers),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Popular Manga Bar Chart
        const popularCtx = document.getElementById('popularMangaChart').getContext('2d');
        const popularData = @json($popular_manga_data);
        
        new Chart(popularCtx, {
            type: 'bar',
            data: {
                labels: popularData.map(item => item.title),
                datasets: [{
                    label: 'Lượt xem',
                    data: popularData.map(item => item.views),
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: '#28a745',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Lượt xem: ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
@endsection
