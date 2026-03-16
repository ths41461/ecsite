<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $addresses = Auth::user()->addresses()->get();
        return Inertia::render('Addresses/Index', [
            'addresses' => $addresses
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Addresses/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip' => 'required|string|max:20',
            'country' => 'required|string|size:2',
            'is_default' => 'boolean'
        ]);

        $addressData = $request->all();
        $addressData['user_id'] = Auth::id();

        // If setting as default, unset other defaults
        if ($request->is_default) {
            Auth::user()->addresses()->update(['is_default' => false]);
        } elseif (!Auth::user()->addresses()->where('is_default', true)->exists()) {
            // If no default exists, make this one default
            $addressData['is_default'] = true;
        }

        $address = UserAddress::create($addressData);

        return response()->json(['message' => 'Address created successfully', 'address' => $address], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserAddress $address)
    {
        // Authorize that the authenticated user owns this address
        $this->authorizeAddressOwnership($address);
        
        return Inertia::render('Addresses/Show', [
            'address' => $address
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserAddress $address)
    {
        // Authorize that the authenticated user owns this address
        $this->authorizeAddressOwnership($address);
        
        return Inertia::render('Addresses/Edit', [
            'address' => $address
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserAddress $address)
    {
        // Authorize that the authenticated user owns this address
        $this->authorizeAddressOwnership($address);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip' => 'required|string|max:20',
            'country' => 'required|string|size:2',
            'is_default' => 'boolean'
        ]);

        $addressData = $request->all();

        // If setting as default, unset other defaults
        if ($request->is_default) {
            Auth::user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            $addressData['is_default'] = true;
        } elseif (!$request->has('is_default')) {
            // If is_default is not in the request, make sure it stays as it was
            $addressData['is_default'] = $address->is_default;
        }

        $address->update($addressData);

        return response()->json(['message' => 'Address updated successfully', 'address' => $address], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserAddress $address)
    {
        // Authorize that the authenticated user owns this address
        $this->authorizeAddressOwnership($address);

        $address->delete();

        return response()->json(['message' => 'Address deleted successfully'], 200);
    }

    /**
     * Set the specified address as the default address.
     */
    public function setDefault(UserAddress $address)
    {
        $this->authorizeAddressOwnership($address);

        // Unset all other defaults
        Auth::user()->addresses()->update(['is_default' => false]);
        
        // Set this one as default
        $address->update(['is_default' => true]);

        return response()->json(['message' => 'Default address updated successfully'], 200);
    }

    /**
     * Authorize that the authenticated user owns the given address.
     */
    private function authorizeAddressOwnership(UserAddress $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403, 'Unauthorized to access this address');
        }
    }
}
