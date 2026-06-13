<?php

namespace App\Http\Controllers;

use App\Services\TrafficService;
use Illuminate\Http\Request;

class TrafficController extends Controller
{
    public function __construct(protected TrafficService $trafficService)
    {
    }

    public function index(Request $request)
    {
        // TODO: list traffic records
    }

    public function store(Request $request)
    {
        // TODO: create traffic record
    }

    public function show(string $id)
    {
        // TODO: show traffic record
    }

    public function update(Request $request, string $id)
    {
        // TODO: update traffic record
    }

    public function destroy(string $id)
    {
        // TODO: delete traffic record
    }
}
