<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\HierarchyTree;

class HierarchyTreeTest extends CrudTest
{
    /**
     * The model to use when creating dummy data
     *
     * @var class
     */
    protected $model = HierarchyTree::class;
    /**
     * The endpoint to query in the API
     * e.g = /api/v1/<endpoint>
     *
     * @var string
     */
    protected $endpoint = 'hierarchy-trees';
    /**
     * Any additional "states" to add to factory
     *
     * @var string
     */
    protected $states = 'strains';
    /**
     * Extra data to pass to POST endpoint
     * aka the (store() method)
     *
     * Must be array (ends up merged with another)
     *
     * @var array
     */
    
    protected $store = [

    ];
    protected $search = true;
}
