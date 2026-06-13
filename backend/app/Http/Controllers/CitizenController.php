<?php

namespace App\Http\Controllers;

use App\Services\CitizenService;
use Illuminate\Http\Request;

class CitizenController extends Controller
{
    public function __construct(protected CitizenService $citizenService)
    {
    }

    public function index(Request $request)
    {
        // TODO: list citizens
    }

    public function store(Request $request)
    {
        // TODO: create citizen
    }

    public function show(string $id)
    {
        // TODO: show citizen
    }

    public function update(Request $request, string $id)
    {
        // TODO: update citizen
    }

    public function destroy(string $id)
    {
        // TODO: delete citizen
    }
}
