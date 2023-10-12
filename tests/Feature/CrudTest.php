<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\AttachJwtToken;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nicolaslopezj\Searchable\SearchableTrait;

abstract class CrudTest extends TestCase
{
    use AttachJwtToken;

    /**
     * Uses the model factory to generate a fake entry
     *
     * @return class
     */
    public function createPost()
    {
        // if ($this->states) {
        //     //return factory($this->model)->states($this->states)->create();
        //     return $this->model::factory()->create();
        // }

        return $this->model::factory()->create();
    }

    /**
     * GET /endpoint/
     * Should return 201 with data array
     *
     * @return void
     */
    public function testIndex()
    {
        $response = $this->json('GET', "api/{$this->endpoint}");
        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => []
            ]);
    }

    /**
     * GET /endpoint/<id>
     * Should return 201 with data array
     *
     * @return void
     */
    public function testShow()
    {
        // Create a test shop with filled out fields
        $activity = $this->createPost();
        // Check the API for the new entry
        $response = $this->json('GET', "api/{$this->endpoint}/{$activity->id}");
        // Delete the test shop
        $activity->delete();
        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => true
            ]);
    }

    /**
     * POST /endpoint/
     *
     * @return void
     */
    public function testStore()
    {
        $activity = $this->createPost();
        $activity = $activity->toArray();
        /**
         * Pass in any extra data
         */
        if ($this->store) {
            $activity = array_merge($activity, $this->store);
        }
        $response = $this->json('POST', "api/{$this->endpoint}/", $activity);
        ($this->model)::destroy($activity['id']);
        $response
            ->assertStatus(201)
            ->assertJson([
                'data' => true
            ]);
    }

    /**
     * DELETE /endpoint/<id>
     * Tests the destroy() method that deletes the shop
     *
     * @return void
     */
    public function testDestroy()
    {
        $activity = $this->createPost();
        $response = $this->json('DELETE', "api/{$this->endpoint}/{$activity->id}");
        $response
            ->assertStatus(200);
    }

    public function testSearch()
    {
        if (isset($this->search)) {
            $search = 'por';
            $response = $this->json('GET', "api/{$this->endpoint}", ['q' => $search]);
            if (isset($this->search)) {
                $response
                    ->assertStatus(200)
                    ->assertJson([
                        'data' => []
                    ]);
            } else {
                $response
                    ->assertStatus(200);
            }
        }
    }

    /**
     * GET /endpoint/<id>
     * Should return 201 with data array
     *
     * @return void
     */
    public function testLike()
    {
        if (isset($this->like)) {
            // Create a test shop with filled out fields
            $activity = $this->createPost();
            // Check the API for the new entry
            $response = $this->json('POST', "api/{$this->endpoint}/{$activity->id}", ['value' => true]);
            if (isset($this->search)) {
                $response
                    ->assertStatus(200)
                    ->assertJson([
                        'data' => true
                    ]);
            } else {
                $response
                    ->assertStatus(200);
            }
        }
    }
}
