<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentTrack;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShipmentTrackingService
{
    /**
     * Track a shipment using its carrier and tracking number
     */
    public function trackShipment(Shipment $shipment): array
    {
        // This is a placeholder implementation
        // In a real application, you would integrate with actual carrier APIs
        $carrier = $shipment->carrier;
        $trackingNumber = $shipment->tracking_number;

        if (!$carrier || !$trackingNumber) {
            throw new \InvalidArgumentException('Carrier and tracking number are required for tracking');
        }

        // For now, return mock tracking data
        // In a real implementation, you would call the carrier's API
        return $this->getMockTrackingData($shipment);
    }

    /**
     * Get mock tracking data for demonstration purposes
     */
    private function getMockTrackingData(Shipment $shipment): array
    {
        // Get existing tracks for this shipment
        $existingTracks = $shipment->shipmentTracks()->get();
        
        // If no tracks exist, create initial mock data
        if ($existingTracks->isEmpty()) {
            return [
                [
                    'status' => 'packed',
                    'event_time' => now()->subDays(2),
                    'location' => 'Warehouse',
                    'description' => 'Package has been packed and is ready for shipment',
                    'carrier' => $shipment->carrier,
                    'track_no' => $shipment->tracking_number,
                ],
                [
                    'status' => 'in_transit',
                    'event_time' => now()->subDay(),
                    'location' => 'Distribution Center',
                    'description' => 'Package is in transit to destination',
                    'carrier' => $shipment->carrier,
                    'track_no' => $shipment->tracking_number,
                ]
            ];
        }

        // Return empty array if tracks already exist (in a real implementation, 
        // you would check for updates from the carrier API)
        return [];
    }

    /**
     * Update shipment status based on tracking events
     */
    public function updateShipmentStatus(Shipment $shipment, array $trackingEvents): void
    {
        foreach ($trackingEvents as $event) {
            // Add the track event to the shipment
            $shipment->addTrack([
                'carrier' => $event['carrier'] ?? $shipment->carrier,
                'track_no' => $event['track_no'] ?? $shipment->tracking_number,
                'status' => $event['status'],
                'payload' => $event,
                'event_time' => $event['event_time'] ?? now(),
            ]);
        }
    }

    /**
     * Sync tracking information from carrier API
     */
    public function syncTracking(Shipment $shipment): void
    {
        try {
            $trackingData = $this->trackShipment($shipment);
            
            if (!empty($trackingData)) {
                $this->updateShipmentStatus($shipment, $trackingData);
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync tracking for shipment: ' . $e->getMessage(), [
                'shipment_id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'carrier' => $shipment->carrier,
            ]);
            
            throw $e;
        }
    }

    /**
     * Get shipment timeline with all tracking events
     */
    public function getShipmentTimeline(Shipment $shipment): array
    {
        $tracks = $shipment->shipmentTracks()
            ->orderBy('event_time', 'desc')
            ->get();

        $timeline = [];
        foreach ($tracks as $track) {
            $timeline[] = [
                'status' => $track->status,
                'event_time' => $track->event_time,
                'location' => $track->raw_event_json['location'] ?? 'Unknown',
                'description' => $track->raw_event_json['description'] ?? 'Shipment status updated',
                'raw_data' => $track->raw_event_json,
            ];
        }

        return $timeline;
    }
}