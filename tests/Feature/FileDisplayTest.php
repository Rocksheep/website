<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\FileCategory;
use Tests\TestCase;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Tests the following cases
 *
 * As anonymous:
 * - all file routes (302 to login)
 *
 * As non-member user:
 * - all file routes (403)
 *
 * As member;
 * - File category list (200)
 * - Category detail on existing category (200)
 * - Category detail on non-existing category (404)
 * - File detail on existing file (200)
 * - File detail on non-existing file (404)
 * - Download on existing file (200)
 * - Download on existing file with missing attachment (404)
 * - Download on non-existing file (404)
 */
class FileDisplayTest extends TestCase
{
    /**
     * Ensures there are some files and categories to work with
     *
     * @return void
     */
    public function seedBefore(): void
    {
        $this->seed('FileSeeder');
    }

    /**
     * Test viewing as logged out user
     *
     * @param string $route
     * @param bool $notFound
     * @return void
     * @dataProvider provideTestRoutes
     */
    public function testViewListAsAnonymous(string $route, bool $notFound)
    {
        // Request the index
        $response = $this->get($route);

        // Handle not found routes
        if ($notFound) {
            $response->assertNotFound();
            return;
        }

        // Expect a response redirecting to login
        $response->assertRedirect(route('login'));
    }

    /**
     * A basic feature test example.
     *
     * @param string $route
     * @param bool $notFound
     * @return void
     * @dataProvider provideTestRoutes
     */
    public function testViewListAsGuest(string $route, bool $notFound)
    {
        // Get a guest user
        $user = $this->getGuestUser();

        // Request the index
        $response = $this->actingAs($user)
                    ->get($route);

        // Expect 404 on not found resources
        if ($notFound) {
            $response->assertNotFound();
            return;
        }

        // Expect 403 on private routes
        $response->assertForbidden();
    }

    /**
     * Test if we're seeing our first category when looking at the file index
     *
     * @return void
     */
    public function testViewIndex()
    {
        $routes = $this->getTestRoutes();
        if (!array_key_exists('home', $routes)) {
            $this->markTestIncomplete('Cannot find [home] key in route list');
        }

        // Get a guest user
        $user = $this->getMemberUser();

        // Request the index
        $response = $this->actingAs($user)
                    ->get($routes['home']);

        // Expect an OK response
        $response->assertOk();

        // Check if we're seeing the first item (they're sorted A-Z)
        $firstModel = $this->getCategoryModel();
        $response->assertSeeText($firstModel->title);
    }

    /**
     * Test if we're seeing the right files when looking at an existing category.
     *
     * @return void
     */
    public function testViewExistingCategory()
    {
        $routes = $this->getTestRoutes();
        if (!array_key_exists('category', $routes)) {
            $this->markTestIncomplete('Cannot find [category] key in route list');
        }

        // Get a guest user
        $user = $this->getMemberUser();

        // Request the index
        $response = $this->actingAs($user)
            ->get($routes['category']);

        // Expect an OK response
        $response->assertOk();

        // Get the first 5 files of this category
        /** @var Collection $fileTitles */
        $fileTitles = $this->getCategoryModel()->files()->take(5)->pluck('title');

        // Can't check if there are no titles
        if (empty($fileTitles)) {
            return;
        }

        // Check if we're getting the files in the same order we're expecting them.
        $response->assertSeeTextInOrder($fileTitles->toArray());
    }

    /**
     * Test if we're getting a 404 when requesting a non-existing category
     *
     * @return void
     */
    public function testViewNonExistingCategory()
    {
        $routes = $this->getTestRoutes();
        if (!array_key_exists('category-missing', $routes)) {
            $this->markTestIncomplete('Cannot find [category-missing] key in route list');
        }

        // Get a guest user
        $user = $this->getMemberUser();

        // Request the index
        $response = $this->actingAs($user)
            ->get($routes['category-missing']);

        // Expect an OK response
        $response->assertNotFound();
    }


    /**
     * Provide translated list of test routes
     *
     * @return array
     */
    public function provideTestRoutes(): array
    {
        // Get all available routes and a list for the result
        $routes = $this->getTestRoutes();
        $result = [];

        // Check all routes for a possible rename
        foreach ($routes as $name => $route) {
            // Prep data
            $data = [$route, Str::endsWith($name, '-missing')];

            // Check for modifiers
            if (preg_match('/^([a-z]+)\-([a-z]+)$/i', $name, $matches)) {
                // Rename it to "<name> (<modifier>)"
                $result["{$matches[1]} ({$matches[2]})"] = $data;
                continue;
            }

            // Otherwise, just use the name
            $result[$name] = $data;
        }

        // Return the list
        return $result;
    }

    /**
     * Provides routes as a predictable list
     *
     * @return string[]
     */
    public function getTestRoutes(): array
    {
        // Make sure we have a router
        $this->ensureApplicationExists();

        // Return test cases
        return [
            // Homepage
            'home' => route('files.index'),

            // Categories
            'category' => route('files.category', [
                'category' => $this->getCategoryModel()
            ]),
            'category-missing' => route('files.category', [
                'category' => sprintf('test-category-%d', time())
            ]),

            // Files
            'file' => route('files.show', [
                'file' => $this->getFileModel()
            ]),
            'file-missing' => route('files.show', [
                'file' => sprintf('test-file-%d', time())
            ]),
        ];
    }

    /**
     * Returns most recent category
     *
     * @return FileCategory|null
     */
    private function getCategoryModel(): ?FileCategory
    {
        // Make sure we have a database connection
        $this->ensureApplicationExists();

        // Return most recent category
        return FileCategory::first();
    }

    /**
     * Returns most recent file
     *
     * @return File|null
     */
    private function getFileModel(): ?File
    {
        // Make sure we have a database connection
        $this->ensureApplicationExists();

        // Return most recent file
        return File::latest()->first();
    }
}