<?php

namespace Ingenius\Shipment\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Ingenius\Auth\Helpers\AuthHelper;
use Ingenius\Core\Http\Controllers\Controller;
use Ingenius\Shipment\Actions\PaginateAddressesAction;
use Ingenius\Shipment\Http\Requests\StoreAddressRequest;
use Ingenius\Shipment\Http\Requests\UpdateAddressRequest;
use Ingenius\Shipment\Models\Address;
use Ingenius\Shipment\Resources\AddressResource;

class AddressController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the authenticated user's addresses.
     */
    public function index(Request $request, PaginateAddressesAction $action): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'viewAny', Address::class);

        $addresses = $action->handle($request->all(), $user->id);

        return Response::api(
            message: 'Addresses fetched successfully',
            data: $addresses->through(fn ($address) => new AddressResource($address))
        );
    }

    /**
     * Store a newly created address.
     */
    public function store(StoreAddressRequest $request): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'create', Address::class);

        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['user_id'] = $user->id;

            // If this is set as default, unset other defaults for this user
            if ($data['is_default'] ?? false) {
                Address::where('user_id', $user->id)
                    ->update(['is_default' => false]);
            }

            $address = Address::create($data);

            DB::commit();

            return Response::api(
                message: 'Address created successfully',
                data: new AddressResource($address),
                code: 201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display the specified address.
     */
    public function show(Address $address): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'view', $address);

        return Response::api(
            message: 'Address fetched successfully',
            data: new AddressResource($address)
        );
    }

    /**
     * Update the specified address.
     */
    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'update', $address);

        DB::beginTransaction();

        try {
            $data = $request->validated();

            // If this is set as default, unset other defaults for this user
            if (($data['is_default'] ?? false) && !$address->is_default) {
                Address::where('user_id', $user->id)
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }

            $address->update($data);

            DB::commit();

            return Response::api(
                message: 'Address updated successfully',
                data: new AddressResource($address->fresh())
            );
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove the specified address.
     */
    public function destroy(Address $address): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'delete', $address);

        $address->delete();

        return Response::api(
            message: 'Address deleted successfully'
        );
    }
}
