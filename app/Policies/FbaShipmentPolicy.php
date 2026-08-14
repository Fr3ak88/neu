<?php

namespace App\Policies;

use App\Models\FbaShipment;
use App\Models\User;

class FbaShipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FbaShipment $shipment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FbaShipment $shipment): bool
    {
        return true;
    }

    public function delete(User $user, FbaShipment $shipment): bool
    {
        return true;
    }
}
