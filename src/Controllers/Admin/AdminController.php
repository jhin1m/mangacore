<?php

namespace Ophim\Core\Controllers\Admin;

use Illuminate\Routing\Controller;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\ReadingProgress;
use Ophim\Core\Models\Theme;
use Ophim\Core\Models\User;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    protected $data = []; // the information we send to the view

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(backpack_middleware());
    }

    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        $this->data['title'] = trans('backpack::base.dashboard'); // set the page title
        $this->data['breadcrumbs'] = [
            trans('backpack::crud.admin')     => backpack_url('dashboard'),
            trans('backpack::base.dashboard') => false,
        ];
        
        // Manga-specific statistics
        $this->data['count_manga'] = Manga::count();
        $this->data['count_chapters'] = Chapter::count();
        $this->data['count_daily_readers'] = $this->getDailyReadersCount();
        $this->data['count_themes'] = Theme::count();
        $this->data['count_users'] = User::count();
        
        // Top manga by views
        $this->data['top_view_day'] = Manga::orderBy('view_day', 'desc')->limit(15)->get();
        $this->data['top_view_week'] = Manga::orderBy('view_week', 'desc')->limit(15)->get();
        $this->data['top_view_month'] = Manga::orderBy('view_month', 'desc')->limit(15)->get();
        
        // Manga status distribution for charts
        $this->data['manga_status_distribution'] = $this->getMangaStatusDistribution();
        
        // Reading activity data for charts
        $this->data['reading_activity_data'] = $this->getReadingActivityData();
        
        // Popular manga data for charts
        $this->data['popular_manga_data'] = $this->getPopularMangaData();
        
        return view(backpack_view('dashboard'), $this->data);
    }

    /**
     * Get count of daily readers (unique users who read today)
     *
     * @return int
     */
    private function getDailyReadersCount()
    {
        return ReadingProgress::whereDate('updated_at', today())
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Get manga status distribution for pie chart
     *
     * @return array
     */
    private function getMangaStatusDistribution()
    {
        return Manga::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                $statusLabels = [
                    'ongoing' => 'Đang cập nhật',
                    'completed' => 'Hoàn thành',
                    'hiatus' => 'Tạm dừng',
                    'cancelled' => 'Đã hủy'
                ];
                return [$statusLabels[$item->status] ?? $item->status => $item->count];
            })
            ->toArray();
    }

    /**
     * Get reading activity data for the last 7 days
     *
     * @return array
     */
    private function getReadingActivityData()
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $readersCount = ReadingProgress::whereDate('updated_at', $date)
                ->distinct('user_id')
                ->count('user_id');
            
            $data[] = [
                'date' => $date->format('M d'),
                'readers' => $readersCount
            ];
        }
        return $data;
    }

    /**
     * Get popular manga data (top 10 by total views)
     *
     * @return array
     */
    private function getPopularMangaData()
    {
        return Manga::orderBy('view_count', 'desc')
            ->limit(10)
            ->get(['title', 'view_count'])
            ->map(function ($manga) {
                return [
                    'title' => strlen($manga->title) > 20 ? substr($manga->title, 0, 20) . '...' : $manga->title,
                    'views' => $manga->view_count
                ];
            })
            ->toArray();
    }

    /**
     * Redirect to the dashboard.
     *
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function redirect()
    {
        // The '/admin' route is not to be used as a page, because it breaks the menu's active state.
        return redirect(backpack_url('dashboard'));
    }
}
