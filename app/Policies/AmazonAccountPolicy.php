<?php

namespace App\Policies;

use App\Models\AmazonAccount;
use App\Models\User;

class AmazonAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AmazonAccount $account): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isFirmenadmin() || $user->isSuperadmin();
    }

    public function update(User $user, AmazonAccount $account): bool
    {
        return $user->isFirmenadmin() || $user->isSuperadmin();
    }

    public function delete(User $user, AmazonAccount $account): bool
    {
        return $user->isFirmenadmin() || $user->isSuperadmin();
    }
}
