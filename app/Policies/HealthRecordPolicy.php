<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HealthRecord;
use App\Models\User;

final class HealthRecordPolicy
{
    /**
     * Determine whether the user can view any health records (index).
     *
     * All authenticated roles may reach the index; the controller scopes
     * the result set (owners see only records for their own pets).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view a specific health record.
     *
     * Vets and admins may view any record. Owners may view a record only
     * if it belongs to one of their own pets.
     */
    public function view(User $user, HealthRecord $healthRecord): bool
    {
        if ($user->isVet() || $user->isAdmin()) {
            return true;
        }

        return $user->id === $healthRecord->pet->owner_user_id;
    }

    /**
     * Determine whether the user can create a health record.
     *
     * Owners and vets may create. Admins are not clinical actors and do
     * not create records through this endpoint. Per-pet ownership for
     * owners is enforced in the controller (an owner may only create for
     * their own pets), since the policy's create() has no model instance.
     */
    public function create(User $user): bool
    {
        return $user->isOwner() || $user->isVet();
    }

    /**
     * Determine whether the user can update a health record.
     *
     * Owners cannot update (records are immutable once logged from their
     * perspective). Vets may update ONLY records they themselves authored
     * (recorded_by_user_id === user id). Admins do not edit clinical data
     * through this endpoint.
     */
    public function update(User $user, HealthRecord $healthRecord): bool
    {
        return $user->isVet()
            && $user->id === $healthRecord->recorded_by_user_id;
    }

    /**
     * Determine whether the user can delete a health record.
     *
     * Only administrators hold pruning clearance. Neither owners nor vets
     * may delete clinical records (an audit-integrity decision — clinical
     * history should not be erasable by the actors who create it).
     */
    public function delete(User $user, HealthRecord $healthRecord): bool
    {
        return $user->isAdmin();
    }
}