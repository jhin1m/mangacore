<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Ophim\Core\Models\User;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;

class ModalAndFieldInteractionTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $user;
    protected $adminRoute;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->adminRoute = config('backpack.base.route_prefix', 'admin');
    }

    /**
     * Test batch upload modal functionality
     *
     * @return void
     */
    public function test_batch_upload_modal_opens_and_closes_properly()
    {
        $manga = Manga::factory()->create();
        $chapter = Chapter::factory()->create(['manga_id' => $manga->id]);

        $this->browse(function (Browser $browser) use ($chapter) {
            $browser->loginAs($this->user)
                    ->visit("/{$this->adminRoute}/chapter")
                    ->waitFor('.batch-upload-btn', 10)
                    ->click('.batch-upload-btn')
                    ->waitFor('#batch-upload-modal', 5)
                    ->assertVisible('#batch-upload-modal')
                    ->assertDontSee('modal-backdrop') // Should not have persistent backdrop
                    ->click('.modal-header .close')
                    ->waitUntilMissing('#batch-upload-modal', 5)
                    ->assertMissing('#batch-upload-modal')
                    ->assertMissing('.modal-backdrop'); // Backdrop should be cleaned up
        });
    }

    /**
     * Test modal backdrop cleanup functionality
     *
     * @return void
     */
    public function test_modal_backdrop_cleanup_on_error()
    {
        $manga = Manga::factory()->create();

        $this->browse(function (Browser $browser) use ($manga) {
            $browser->loginAs($this->user)
                    ->visit("/{$this->adminRoute}/chapter")
                    ->waitFor('.batch-upload-btn', 10)
                    ->click('.batch-upload-btn')
                    ->waitFor('#batch-upload-modal', 5)
                    ->assertVisible('#batch-upload-modal')
                    // Simulate error by clicking outside modal
                    ->click('body')
                    ->pause(1000)
                    // Modal should close and backdrop should be cleaned
                    ->waitUntilMissing('#batch-upload-modal', 5)
                    ->assertMissing('.modal-backdrop')
                    // Interface should remain interactive
                    ->assertEnabled('.batch-upload-btn');
        });
    }

    /**
     * Test progress modal error recovery
     *
     * @return void
     */
    public function test_progress_modal_error_recovery()
    {
        $manga = Manga::factory()->create();

        $this->browse(function (Browser $browser) use ($manga) {
            $browser->loginAs($this->user)
                    ->visit("/{$this->adminRoute}/chapter")
                    ->waitFor('.batch-upload-btn', 10)
                    ->click('.batch-upload-btn')
                    ->waitFor('#batch-upload-modal', 5)
                    ->assertVisible('#batch-upload-modal')
                    // Start upload process (this would normally trigger progress modal)
                    ->attach('input[type="file"]', __DIR__.'/fixtures/test-chapter.zip')
                    ->click('.start-upload-btn')
                    ->waitFor('#upload-progress-modal', 10)
                    ->assertVisible('#upload-progress-modal')
                    // Test force close functionality
                    ->waitFor('.force-close-progress', 5)
                    ->click('.force-close-progress')
                    ->waitUntilMissing('#upload-progress-modal', 5)
                    ->assertMissing('#upload-progress-modal')
                    ->assertMissing('.modal-backdrop');
        });
    }

    /**
     * Test emergency recovery mechanisms
     *
     * @return void
     */
    public function test_emergency_recovery_mechanisms()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit("/{$this->adminRoute}/chapter")
                    ->waitFor('.batch-upload-btn', 10)
                    ->click('.batch-upload-btn')
                    ->waitFor('#batch-upload-modal', 5)
                    ->assertVisible('#batch-upload-modal')
                    // Test emergency recovery button
                    ->waitFor('.modal-recovery-btn', 5)
                    ->click('.modal-recovery-btn')
                    ->pause(2000) // Allow recovery script to run
                    ->assertMissing('.modal-backdrop')
                    ->assertEnabled('.batch-upload-btn');
        });
    }

    /**
     * Test field data type handling in forms
     *
     * @return void
     */
    public function test_field_data_type_handling_in_forms()
    {
        $manga = Manga::factory()->create([
            'other_name' => 'Alt Name 1, Alt Name 2, Alt Name 3'
        ]);

        $this->browse(function (Browser $browser) use ($manga) {
            $browser->loginAs($this->user)
                    ->visit("/{$this->adminRoute}/manga/{$manga->id}/edit")
                    ->waitFor('input[name="other_name"]', 10)
                    ->assertInputValue('other_name', 'Alt Name 1, Alt Name 2, Alt Name 3')
                    // Should not see any error messages about array conversion
                    ->assertDontSee('Array to string conversion')
                    ->assertDontSee('htmlspecialchars() expects parameter 1 to be string')
                    // Test updating the field
                    ->clear('other_name')
                    ->type('other_name', 'Updated Alt 1, Updated Alt 2')
                    ->press('Save')
                    ->waitForText('successfully', 10)
                    ->assertPathIs("/{$this->adminRoute}/manga");
        });
    }

    /**
     * Test manga selection field in chapter creation
     *
     * @return void
     */
    public function test_manga_selection_field_functionality()
    {
        $manga1 = Manga::factory()->create(['title' => 'Test Manga 1']);
        $manga2 = Manga::factory()->create(['title' => 'Test Manga 2']);

        $this->browse(function (Browser $browser) use ($manga1, $manga2) {
            $browser->loginAs($this->user)
                    ->visit("/{$this->adminRoute}/chapter/create")
                    ->waitFor('select[name="manga_id"]', 10)
                    // Should not see field view resolution errors
                    ->assertDontSee('View not found')
                    ->assertDontSee('select_manga')
                    // Should see manga options
                    ->assertSeeIn('select[name="manga_id"]', $manga1->title)
                    ->assertSeeIn('select[name="manga_id"]', $manga2->title)
                    // Test selecting a manga
                    ->select('manga_id', $manga1->id)
                    ->type('title', 'Test Chapter')
                    ->type('chapter_number', '1.0')
                    ->press('Save')
                    ->waitForText('successfully', 10)
                    ->assertPathIs("/{$this->adminRoute}/chapter");
        });
    }

    /**
     * Test textarea field handling with array data
     *
     * @return void
     */
    public function test_textarea_field_array_data_handling()
    {
        $manga = Manga::factory()->create([
            'description' => 'Line 1, Line 2, Line 3'
        ]);

        $this->browse(function (Browser $browser) use ($manga) {
            $browser->loginAs($this->user)
                    ->visit("/{$this->adminRoute}/manga/{$manga->id}/edit")
                    ->waitFor('textarea[name="description"]', 10)
                    ->assertInputValue('description', 'Line 1, Line 2, Line 3')
                    // Should not see array conversion errors
                    ->assertDontSee('Array to string conversion')
                    // Test updating textarea
                    ->clear('description')
                    ->type('description', 'Updated description with special chars & symbols')
                    ->press('Save')
                    ->waitForText('successfully', 10);
        });
    }

    /**
     * Test modal JavaScript error handling
     *
     * @return void
     */
    public function test_modal_javascript_error_handling()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit("/{$this->adminRoute}/chapter")
                    ->waitFor('.batch-upload-btn', 10)
                    // Inject JavaScript error to test error handling
                    ->script('window.testError = function() { throw new Error("Test error"); }')
                    ->click('.batch-upload-btn')
                    ->waitFor('#batch-upload-modal', 5)
                    ->assertVisible('#batch-upload-modal')
                    // Trigger error and test recovery
                    ->script('try { window.testError(); } catch(e) { console.log("Error handled"); }')
                    ->pause(1000)
                    // Modal should still be functional
                    ->assertVisible('#batch-upload-modal')
                    ->click('.modal-header .close')
                    ->waitUntilMissing('#batch-upload-modal', 5)
                    ->assertMissing('.modal-backdrop');
        });
    }

    /**
     * Test upload state management
     *
     * @return void
     */
    public function test_upload_state_management()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit("/{$this->adminRoute}/chapter")
                    ->waitFor('.batch-upload-btn', 10)
                    ->click('.batch-upload-btn')
                    ->waitFor('#batch-upload-modal', 5)
                    ->assertVisible('#batch-upload-modal')
                    // Test upload button state
                    ->assertEnabled('.start-upload-btn')
                    // Simulate file selection
                    ->attach('input[type="file"]', __DIR__.'/fixtures/test-chapter.zip')
                    ->pause(1000)
                    // Button should remain enabled after file selection
                    ->assertEnabled('.start-upload-btn')
                    // Test upload initiation
                    ->click('.start-upload-btn')
                    ->pause(2000)
                    // During upload, button should be disabled
                    ->assertAttribute('.start-upload-btn', 'disabled', 'true');
        });
    }

    /**
     * Test form validation error display
     *
     * @return void
     */
    public function test_form_validation_error_display()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit("/{$this->adminRoute}/manga/create")
                    ->waitFor('input[name="title"]', 10)
                    // Submit form without required fields
                    ->press('Save')
                    ->waitForText('error', 10)
                    // Should see validation errors
                    ->assertSee('required')
                    // Form should remain functional
                    ->type('title', 'Test Manga')
                    ->press('Save')
                    ->waitForText('successfully', 10);
        });
    }

    /**
     * Test responsive modal behavior
     *
     * @return void
     */
    public function test_responsive_modal_behavior()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit("/{$this->adminRoute}/chapter")
                    ->waitFor('.batch-upload-btn', 10)
                    // Test on different screen sizes
                    ->resize(1200, 800) // Desktop
                    ->click('.batch-upload-btn')
                    ->waitFor('#batch-upload-modal', 5)
                    ->assertVisible('#batch-upload-modal')
                    ->click('.modal-header .close')
                    ->waitUntilMissing('#batch-upload-modal', 5)
                    // Test on mobile size
                    ->resize(375, 667) // Mobile
                    ->click('.batch-upload-btn')
                    ->waitFor('#batch-upload-modal', 5)
                    ->assertVisible('#batch-upload-modal')
                    ->click('.modal-header .close')
                    ->waitUntilMissing('#batch-upload-modal', 5)
                    ->assertMissing('.modal-backdrop');
        });
    }

    /**
     * Test keyboard navigation and accessibility
     *
     * @return void
     */
    public function test_keyboard_navigation_and_accessibility()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit("/{$this->adminRoute}/chapter")
                    ->waitFor('.batch-upload-btn', 10)
                    ->click('.batch-upload-btn')
                    ->waitFor('#batch-upload-modal', 5)
                    ->assertVisible('#batch-upload-modal')
                    // Test ESC key closes modal
                    ->keys('', '{escape}')
                    ->waitUntilMissing('#batch-upload-modal', 5)
                    ->assertMissing('#batch-upload-modal')
                    ->assertMissing('.modal-backdrop');
        });
    }

    /**
     * Test concurrent modal operations
     *
     * @return void
     */
    public function test_concurrent_modal_operations()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit("/{$this->adminRoute}/chapter")
                    ->waitFor('.batch-upload-btn', 10)
                    // Open modal
                    ->click('.batch-upload-btn')
                    ->waitFor('#batch-upload-modal', 5)
                    ->assertVisible('#batch-upload-modal')
                    // Try to open another modal (should handle gracefully)
                    ->click('.batch-upload-btn')
                    ->pause(1000)
                    // Should still have only one modal
                    ->assertVisible('#batch-upload-modal')
                    ->script('return document.querySelectorAll(".modal").length')
                    ->assertScript('return document.querySelectorAll(".modal").length', 1)
                    // Close modal
                    ->click('.modal-header .close')
                    ->waitUntilMissing('#batch-upload-modal', 5)
                    ->assertMissing('.modal-backdrop');
        });
    }
}