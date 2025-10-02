<?php

namespace Ingenius\Shipment\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Ingenius\Core\Helpers\AuthHelper;
use Ingenius\Core\Http\Controllers\Controller;
use Ingenius\Shipment\Actions\BulkActivateZonesAction;
use Ingenius\Shipment\Http\Requests\ZonesBulkActivationRequest;
use Ingenius\Shipment\Models\Zone;

class ZoneController extends Controller {

    use AuthorizesRequests;

    public function index(Request $request): JsonResponse {

        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'viewAny', Zone::class);

        $zones = Zone::orderBy('id')->get();

        return Response::api(data: $zones, message: 'Zones fetched successfully');
    }

    public function bulkActivation(ZonesBulkActivationRequest $request, BulkActivateZonesAction $bulkActivateZonesAction): JsonResponse {

        $user = AuthHelper::getUser();

        $this->authorizeForUser($user, 'activate', Zone::class);

        $bulkActivateZonesAction->handle($request->validated());

        return Response::api(message: 'Zones updated successfully');
    }

}