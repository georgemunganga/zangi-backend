<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SellerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search', '');
        $status = $request->query('status', 'all');
        $perPage = (int) $request->query('per_page', 20);

        $query = Seller::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $sellers = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $sellers->items(),
            'pagination' => [
                'current_page' => $sellers->currentPage(),
                'per_page' => $sellers->perPage(),
                'total' => $sellers->total(),
                'last_page' => $sellers->lastPage(),
            ],
        ]);
    }

    public function show(Seller $seller): JsonResponse
    {
        return response()->json([
            'seller' => $seller,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:32|unique:sellers,code',
            'phone' => 'required|string|max:20|unique:sellers,phone',
            'pin' => 'required|string|min:4|max:6',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $seller = Seller::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'phone' => $validated['phone'],
            'pin_hash' => Hash::make($validated['pin']),
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Seller created successfully.',
            'seller' => $seller,
        ], 201);
    }

    public function update(Request $request, Seller $seller): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => ['sometimes', 'required', 'string', 'max:32', Rule::unique('sellers')->ignore($seller->id)],
            'phone' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('sellers')->ignore($seller->id)],
            'status' => 'sometimes|required|in:active,inactive,suspended',
        ]);

        if (isset($validated['pin'])) {
            $validated['pin_hash'] = Hash::make($validated['pin']);
            unset($validated['pin']);
        }

        $seller->update($validated);

        return response()->json([
            'message' => 'Seller updated successfully.',
            'seller' => $seller->fresh(),
        ]);
    }

    public function destroy(Seller $seller): JsonResponse
    {
        $seller->delete();

        return response()->json([
            'message' => 'Seller deleted successfully.',
        ]);
    }

    public function resetPin(Request $request, Seller $seller): JsonResponse
    {
        $validated = $request->validate([
            'newPin' => 'required|string|min:4|max:6',
        ]);

        $seller->update([
            'pin_hash' => Hash::make($validated['newPin']),
        ]);

        return response()->json([
            'message' => 'PIN reset successfully.',
        ]);
    }

    public function stats(): JsonResponse
    {
        $total = Seller::count();
        $active = Seller::where('status', 'active')->count();
        $inactive = Seller::where('status', 'inactive')->count();
        $suspended = Seller::where('status', 'suspended')->count();

        return response()->json([
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'suspended' => $suspended,
        ]);
    }
}
