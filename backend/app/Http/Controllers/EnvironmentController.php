<?php

namespace App\Http\Controllers;

use App\Services\EnvironmentService;
use Illuminate\Http\Request;

class EnvironmentController extends Controller
{
    public function __construct(protected EnvironmentService $environmentService)
    {
    }

    public function index(Request $request)
    {
        // TODO: list environment sensor records
    }

    public function store(Request $request)
    {
        // TODO: create environment sensor record
    }

    public function show(string $id)
    {
        // TODO: show environment sensor record
    }

    public function update(Request $request, string $id)
    {
        // TODO: update environment sensor record
    }

    public function destroy(string $id)
    {
        // TODO: delete environment sensor record
    }
}
