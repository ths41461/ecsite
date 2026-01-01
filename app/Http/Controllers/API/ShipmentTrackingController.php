<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\ShipmentTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShipmentTrackingController extends Controller
{
    protected ShipmentTrackingService $shipmentTrackingService;

    public function __construct(ShipmentTrackingService $shipmentTrackingService)
    {
        $this->shipmentTrackingService = $shipmentTrackingService;
    }

    /**
     * Get shipment tracking information by tracking number
     */
    public function trackByTrackingNumber(Request $request): JsonResponse
    {
        $request->validate([
            'tracking_number' => 'required|string',
        ]);

        $trackingNumber = $request->input('tracking_number');

        $shipment = Shipment::where('tracking_number', $trackingNumber)->first();

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found with the provided tracking number.'
            ], 404);
        }

        try {
            $timeline = $this->shipmentTrackingService->getShipmentTimeline($shipment);

            return response()->json([
                'success' => true,
                'data' => [
                    'shipment' => [
                        'id' => $shipment->id,
                        'order_id' => $shipment->order_id,
                        'carrier' => $shipment->carrier,
                        'tracking_number' => $shipment->tracking_number,
                        'status' => $shipment->status,
                        'shipment_status' => $shipment->shipmentStatus?->name,
                        'shipped_at' => $shipment->shipped_at,
                        'delivered_at' => $shipment->delivered_at,
                    ],
                    'timeline' => $timeline,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving shipment tracking information: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shipment tracking information by shipment ID
     */
    public function trackByShipmentId(int $id): JsonResponse
    {
        $shipment = Shipment::with(['order', 'shipmentStatus'])->find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.'
            ], 404);
        }

        try {
            $timeline = $this->shipmentTrackingService->getShipmentTimeline($shipment);

            return response()->json([
                'success' => true,
                'data' => [
                    'shipment' => [
                        'id' => $shipment->id,
                        'order_id' => $shipment->order_id,
                        'carrier' => $shipment->carrier,
                        'tracking_number' => $shipment->tracking_number,
                        'status' => $shipment->status,
                        'shipment_status' => $shipment->shipmentStatus?->name,
                        'shipped_at' => $shipment->shipped_at,
                        'delivered_at' => $shipment->delivered_at,
                    ],
                    'timeline' => $timeline,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving shipment tracking information: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync shipment tracking information from carrier
     */
    public function syncTracking(int $id): JsonResponse
    {
        $shipment = Shipment::find($id);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.'
            ], 404);
        }

        try {
            $this->shipmentTrackingService->syncTracking($shipment);

            return response()->json([
                'success' => true,
                'message' => 'Shipment tracking synchronized successfully.',
                'data' => [
                    'shipment' => $shipment->refresh()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error synchronizing shipment tracking: ' . $e->getMessage()
            ], 500);
        }
    }
}