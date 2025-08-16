<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test environment
        $this->setUpTestEnvironment();
    }

    protected function setUpTestEnvironment()
    {
        // Configure test database
        config(['database.default' => 'testing']);
        config(['database.connections.testing' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        // Configure test storage
        config(['filesystems.default' => 'testing']);
        config(['filesystems.disks.testing' => [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks'),
        ]]);

        // Configure test cache
        config(['cache.default' => 'array']);

        // Configure test session
        config(['session.driver' => 'array']);

        // Configure test queue
        config(['queue.default' => 'sync']);

        // Disable broadcasting for tests
        config(['broadcasting.default' => 'null']);

        // Configure Backpack settings for tests
        config(['backpack.base.route_prefix' => 'admin']);
        config(['backpack.base.web_middleware' => ['web']]);
    }

    protected function refreshTestDatabase()
    {
        if ($this->app) {
            foreach ($this->connectionsToTransact() as $name) {
                $connection = $this->app['db']->connection($name);
                $dispatcher = $connection->getEventDispatcher();
                $connection->unsetEventDispatcher();
                $connection->disconnect();
                if ($dispatcher) {
                    $connection->setEventDispatcher($dispatcher);
                }
            }
        }

        $this->artisan('migrate:fresh', [
            '--database' => 'testing',
        ]);
    }

    /**
     * Create application instance
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Helper method to create authenticated user for admin tests
     */
    protected function actingAsAdmin()
    {
        $user = \Ophim\Core\Models\User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        return $this->actingAs($user);
    }

    /**
     * Helper method to create test manga with relationships
     */
    protected function createTestManga($attributes = [])
    {
        $manga = \Ophim\Core\Models\Manga::factory()->create($attributes);
        
        // Add some default relationships
        $author = \Ophim\Core\Models\Author::factory()->create();
        $artist = \Ophim\Core\Models\Artist::factory()->create();
        $category = \Ophim\Core\Models\Category::factory()->create();
        
        $manga->authors()->attach($author);
        $manga->artists()->attach($artist);
        $manga->categories()->attach($category);
        
        return $manga;
    }

    /**
     * Helper method to create test chapter with pages
     */
    protected function createTestChapter($mangaId = null, $pageCount = 5)
    {
        if (!$mangaId) {
            $manga = $this->createTestManga();
            $mangaId = $manga->id;
        }

        $chapter = \Ophim\Core\Models\Chapter::factory()->create([
            'manga_id' => $mangaId,
            'page_count' => $pageCount
        ]);

        // Create pages for the chapter
        for ($i = 1; $i <= $pageCount; $i++) {
            \Ophim\Core\Models\Page::factory()->create([
                'chapter_id' => $chapter->id,
                'page_number' => $i
            ]);
        }

        return $chapter;
    }

    /**
     * Assert that a model has the expected relationships
     */
    protected function assertHasRelationships($model, array $relationships)
    {
        foreach ($relationships as $relationship) {
            $this->assertTrue(
                method_exists($model, $relationship),
                "Model " . get_class($model) . " should have {$relationship} relationship"
            );
        }
    }

    /**
     * Assert that a model uses the expected traits
     */
    protected function assertUsesTraits($model, array $traits)
    {
        $modelTraits = class_uses_recursive(get_class($model));
        
        foreach ($traits as $trait) {
            $this->assertContains(
                $trait,
                $modelTraits,
                "Model " . get_class($model) . " should use {$trait} trait"
            );
        }
    }

    /**
     * Assert that a model implements the expected interfaces
     */
    protected function assertImplementsInterfaces($model, array $interfaces)
    {
        foreach ($interfaces as $interface) {
            $this->assertInstanceOf(
                $interface,
                $model,
                "Model " . get_class($model) . " should implement {$interface} interface"
            );
        }
    }
}