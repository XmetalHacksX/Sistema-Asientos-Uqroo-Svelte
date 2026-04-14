<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CampusController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Campuses/Index', [
            'campuses' => Campus::query()->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Campuses/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Campus::create($data);

        return redirect()->route('admin.campuses.index');
    }

    public function edit(Campus $campus)
    {
        return Inertia::render('Admin/Campuses/Edit', [
            'campus' => $campus,
        ]);
    }

    public function update(Request $request, Campus $campus)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $campus->update($data);

        return redirect()->route('admin.campuses.index');
    }

    public function destroy(Campus $campus)
    {
        $campus->delete();

        return redirect()->route('admin.campuses.index');
    }
}

