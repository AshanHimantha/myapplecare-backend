<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Repair;
use Illuminate\Http\Request;

class RepairController extends Controller
{
    public function index(Request $request)
    {
        $repairs = Repair::latest()->paginate(10);
        return response()->json([
            'status' => 'success',
            'data' => $repairs
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'repair_name' => 'required|string',
            'device_category' => 'required|in:iphone,android,other',
            'cost' => 'required|numeric|min:0',
            'description' => 'nullable|string'
        ]);

        $repair = Repair::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Repair created successfully',
            'data' => $repair
        ], 201);
    }

    public function show($id)
    {
        $repair = Repair::findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $repair
        ]);
    }

    public function update(Request $request, $id)
    {
        $repair = Repair::findOrFail($id);

        $request->validate([
            'repair_name' => 'required|string',
            'device_category' => 'required|in:iphone,android,other',
            'cost' => 'required|numeric|min:0',
            'description' => 'nullable|string'
        ]);

        $repair->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Repair updated successfully',
            'data' => $repair
        ]);
    }

    public function search(Request $request)
    {
        $search = $request->input('search');
        $deviceCategory = $request->input('device_category');

        $query = Repair::query();

        if ($search) {
            $query->where('repair_name', 'LIKE', "%{$search}%");
        }

        if ($deviceCategory) {
            $query->where('device_category', $deviceCategory);
        }

        $repairs = $query->orderBy('updated_at', 'asc')
                    ->paginate(4);

        return response()->json([
            'status' => 'success',
            'data' => $repairs->items(),
            'meta' => [
                'current_page' => $repairs->currentPage(),
                'last_page' => $repairs->lastPage(),
                'per_page' => $repairs->perPage(),
                'total' => $repairs->total()
            ]
        ]);
    }

    public function destroy($id)
    {
        $repair = Repair::findOrFail($id);
        $repair->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Repair deleted successfully'
        ]);
    }
}
