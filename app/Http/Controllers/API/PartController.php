<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Part;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PartController extends Controller
{
    public function index()
    {
        $parts = Part::latest()->get();
        return response()->json([
            'status' => 'success',
            'data' => $parts
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'part_name' => 'required|string',
            'part_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'quantity' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'device_category' => 'required|in:iphone,android,other',
            'grade' => 'required|in:a,b,c',
            'description' => 'nullable|string'
        ]);

        $data = $request->all();

        if ($request->hasFile('part_image')) {
            $file = $request->file('part_image');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '.' . $extension;
            $file->move(public_path('storage/parts'), $filename);
            $data['part_image'] =  $filename;
        }

        $part = Part::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Part created successfully',
            'data' => $part
        ], 201);
    }

    public function show($id)
    {
        $part = Part::findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $part
        ]);
    }

    public function update(Request $request, $id)
    {
        $part = Part::findOrFail($id);

        $request->validate([
            'part_name' => 'required|string',
            'part_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'quantity' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'device_category' => 'required|in:iphone,android,other',
            'grade' => 'required|in:a,b,c',
            'description' => 'nullable|string'
        ]);

        $data = $request->all();

        if ($request->hasFile('part_image')) {
            // Delete old image
            if ($part->part_image) {
                Storage::delete('public/parts/' . $part->part_image);
            }

            $file = $request->file('part_image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/parts', $filename);
            $data['part_image'] = $filename;
        }

        $part->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Part updated successfully',
            'data' => $part
        ]);
    }

    public function destroy($id)
    {
        $part = Part::findOrFail($id);

        if ($part->part_image) {
            Storage::delete('public/parts/' . $part->part_image);
        }

        $part->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Part deleted successfully'
        ]);
    }

    public function search(Request $request)
    {
        $search = $request->input('search');
        $deviceCategory = $request->input('device_category');
        $grade = $request->input('grade');
        $perPage = $request->input('per_page', 20);

        $query = Part::query()
            ->select([
                'id',
                'part_name',
                'part_image',
                'quantity',
                'unit_price',
                'selling_price',
                'device_category',
                'grade',
                'description'
            ]);

        // Apply search filter
        if ($search) {
            $query->where('part_name', 'LIKE', "%{$search}%");
        }

        // Apply device category filter
        if ($deviceCategory) {
            $query->where('device_category', $deviceCategory);
        }

        // Apply grade filter
        if ($grade) {
            $query->where('grade', $grade);
        }

        $parts = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $parts->items(),
            'meta' => [
                'current_page' => $parts->currentPage(),
                'last_page' => $parts->lastPage(),
                'per_page' => $parts->perPage(),
                'total' => $parts->total()
            ]
        ]);
    }

}
