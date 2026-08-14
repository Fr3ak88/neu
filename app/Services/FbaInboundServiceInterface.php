<?php

namespace App\Services;

use App\Models\FbaShipment;

interface FbaInboundServiceInterface
{
    public function createInboundPlan(FbaShipment $shipment): void;
    public function register(FbaShipment $shipment): void;

    // Plan-Verwaltung
    public function cancelPlan(FbaShipment $shipment): void;
    public function listInboundPlans(string $marketplaceId, array $filters = []): array;
    public function getInboundPlan(string $planId): array;

    // LTL
    public function submitPalletInfo(FbaShipment $shipment): void;

    // Labels
    public function generateLabels(FbaShipment $shipment): array;

    // Prep Details
    public function setPrepDetails(FbaShipment $shipment): void;
    public function listPrepDetails(string $marketplaceId): array;

    // Shipment
    public function listShipmentItems(string $planId, string $shipmentId): array;
    public function updateShipmentName(string $planId, string $shipmentId, string $name): void;

    // PCP (Partnered Carrier)
    public function purchaseShipment(string $planId, string $shipmentId, string $transportationOptionId): array;
    public function getShipmentPurchaseStatus(string $planId, string $shipmentId): array;

    // Status
    public function getShipmentStatus(FbaShipment $shipment): array;

    // Inventory
    public function getInventorySummaries(string $marketplaceId, string $granularityId): array;
}
