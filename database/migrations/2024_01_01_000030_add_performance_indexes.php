<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mangas', function (Blueprint $table) {
            // Add composite indexes for common query patterns
            $table->index(['status', 'type', 'demographic'], 'idx_manga_status_type_demo');
            $table->index(['is_recommended', 'view_count'], 'idx_manga_recommended_views');
            $table->index(['is_completed', 'updated_at'], 'idx_manga_completed_updated');
            $table->index(['publication_year', 'rating'], 'idx_manga_year_rating');
            $table->index(['view_day', 'created_at'], 'idx_manga_daily_views');
            $table->index(['view_week', 'created_at'], 'idx_manga_weekly_views');
            $table->index(['view_month', 'created_at'], 'idx_manga_monthly_views');
            $table->index(['is_adult_content', 'status'], 'idx_manga_adult_status');
        });

        Schema::table('chapters', function (Blueprint $table) {
            // Add composite indexes for common query patterns
            $table->index(['manga_id', 'published_at', 'chapter_number'], 'idx_chapter_manga_published');
            $table->index(['published_at', 'is_premium'], 'idx_chapter_published_premium');
            $table->index(['manga_id', 'volume_number', 'chapter_number'], 'idx_chapter_manga_volume');
            $table->index(['view_count', 'published_at'], 'idx_chapter_views_published');
            $table->index(['is_premium', 'manga_id'], 'idx_chapter_premium_manga');
        });

        Schema::table('pages', function (Blueprint $table) {
            // Add index for page ordering and chapter queries
            $table->index(['chapter_id', 'page_number'], 'idx_page_chapter_number');
        });

        Schema::table('reading_progress', function (Blueprint $table) {
            // Add composite indexes for reading progress queries
            $table->index(['user_id', 'manga_id', 'updated_at'], 'idx_progress_user_manga_updated');
            $table->index(['manga_id', 'completed_at'], 'idx_progress_manga_completed');
            $table->index(['user_id', 'completed_at'], 'idx_progress_user_completed');
        });

        // Add indexes to pivot tables for better relationship queries
        Schema::table('author_manga', function (Blueprint $table) {
            $table->index(['author_id', 'manga_id'], 'idx_manga_author_lookup');
        });

        Schema::table('artist_manga', function (Blueprint $table) {
            $table->index(['artist_id', 'manga_id'], 'idx_manga_artist_lookup');
        });

        Schema::table('category_manga', function (Blueprint $table) {
            $table->index(['category_id', 'manga_id'], 'idx_manga_category_lookup');
        });

        Schema::table('manga_tag', function (Blueprint $table) {
            $table->index(['tag_id', 'manga_id'], 'idx_manga_tag_lookup');
        });

        Schema::table('manga_origin', function (Blueprint $table) {
            $table->index(['origin_id', 'manga_id'], 'idx_manga_origin_lookup');
        });

        Schema::table('manga_publisher', function (Blueprint $table) {
            $table->index(['publisher_id', 'manga_id'], 'idx_manga_publisher_lookup');
        });

        // Add indexes for user manga interactions
        if (Schema::hasTable('manga_user')) {
            Schema::table('manga_user', function (Blueprint $table) {
                $table->index(['user_id', 'is_favorite'], 'idx_manga_user_favorite');
                $table->index(['manga_id', 'rating'], 'idx_manga_user_rating');
                $table->index(['user_id', 'updated_at'], 'idx_manga_user_updated');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mangas', function (Blueprint $table) {
            $table->dropIndex('idx_manga_status_type_demo');
            $table->dropIndex('idx_manga_recommended_views');
            $table->dropIndex('idx_manga_completed_updated');
            $table->dropIndex('idx_manga_year_rating');
            $table->dropIndex('idx_manga_daily_views');
            $table->dropIndex('idx_manga_weekly_views');
            $table->dropIndex('idx_manga_monthly_views');
            $table->dropIndex('idx_manga_adult_status');
        });

        Schema::table('chapters', function (Blueprint $table) {
            $table->dropIndex('idx_chapter_manga_published');
            $table->dropIndex('idx_chapter_published_premium');
            $table->dropIndex('idx_chapter_manga_volume');
            $table->dropIndex('idx_chapter_views_published');
            $table->dropIndex('idx_chapter_premium_manga');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex('idx_page_chapter_number');
        });

        Schema::table('reading_progress', function (Blueprint $table) {
            $table->dropIndex('idx_progress_user_manga_updated');
            $table->dropIndex('idx_progress_manga_completed');
            $table->dropIndex('idx_progress_user_completed');
        });

        Schema::table('author_manga', function (Blueprint $table) {
            $table->dropIndex('idx_manga_author_lookup');
        });

        Schema::table('artist_manga', function (Blueprint $table) {
            $table->dropIndex('idx_manga_artist_lookup');
        });

        Schema::table('category_manga', function (Blueprint $table) {
            $table->dropIndex('idx_manga_category_lookup');
        });

        Schema::table('manga_tag', function (Blueprint $table) {
            $table->dropIndex('idx_manga_tag_lookup');
        });

        Schema::table('manga_origin', function (Blueprint $table) {
            $table->dropIndex('idx_manga_origin_lookup');
        });

        Schema::table('manga_publisher', function (Blueprint $table) {
            $table->dropIndex('idx_manga_publisher_lookup');
        });

        if (Schema::hasTable('manga_user')) {
            Schema::table('manga_user', function (Blueprint $table) {
                $table->dropIndex('idx_manga_user_favorite');
                $table->dropIndex('idx_manga_user_rating');
                $table->dropIndex('idx_manga_user_updated');
            });
        }
    }
}