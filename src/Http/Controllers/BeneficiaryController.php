<?php

namespace Ingenius\Shipment\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Ingenius\Auth\Helpers\AuthHelper;
use Ingenius\Core\Http\Controllers\Controller;
use Ingenius\Shipment\Actions\PaginateBeneficiariesAction;
use Ingenius\Shipment\Http\Requests\StoreBeneficiaryRequest;
use Ingenius\Shipment\Http\Requests\UpdateBeneficiaryRequest;
use Ingenius\Shipment\Models\Beneficiary;
use Ingenius\Shipment\Resources\BeneficiaryResource;

class BeneficiaryController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the authenticated user's beneficiaries.
     */
    public function index(Request $request, PaginateBeneficiariesAction $action): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'viewAny', Beneficiary::class);

        $beneficiaries = $action->handle($request->all(), $user->id);

        return Response::api(
            message: 'Beneficiaries fetched successfully',
            data: $beneficiaries->through(fn ($beneficiary) => new BeneficiaryResource($beneficiary))
        );
    }

    /**
     * Store a newly created beneficiary.
     */
    public function store(StoreBeneficiaryRequest $request): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'create', Beneficiary::class);

        DB::beginTransaction();

        try {
            $data = $request->validated();
            $data['user_id'] = $user->id;

            // If this is set as default, unset other defaults for this user
            if ($data['is_default'] ?? false) {
                Beneficiary::where('user_id', $user->id)
                    ->update(['is_default' => false]);
            }

            $beneficiary = Beneficiary::create($data);

            DB::commit();

            return Response::api(
                message: 'Beneficiary created successfully',
                data: new BeneficiaryResource($beneficiary),
                code: 201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display the specified beneficiary.
     */
    public function show(Beneficiary $beneficiary): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'view', $beneficiary);

        return Response::api(
            message: 'Beneficiary fetched successfully',
            data: new BeneficiaryResource($beneficiary)
        );
    }

    /**
     * Update the specified beneficiary.
     */
    public function update(UpdateBeneficiaryRequest $request, Beneficiary $beneficiary): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'update', $beneficiary);

        DB::beginTransaction();

        try {
            $data = $request->validated();

            // If this is set as default, unset other defaults for this user
            if (($data['is_default'] ?? false) && !$beneficiary->is_default) {
                Beneficiary::where('user_id', $user->id)
                    ->where('id', '!=', $beneficiary->id)
                    ->update(['is_default' => false]);
            }

            $beneficiary->update($data);

            DB::commit();

            return Response::api(
                message: 'Beneficiary updated successfully',
                data: new BeneficiaryResource($beneficiary->fresh())
            );
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove the specified beneficiary.
     */
    public function destroy(Beneficiary $beneficiary): JsonResponse
    {
        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'delete', $beneficiary);

        $beneficiary->delete();

        return Response::api(
            message: 'Beneficiary deleted successfully'
        );
    }
}
