<?php

namespace App\Policies;

use App\Models\QrPage;
use App\Models\User;

class QrPagePolicy
{
    public function view(User $u, QrPage $p): bool
    {
        return $u->id === $p->user_id;
    }

    public function update(User $u, QrPage $p): bool
    {
        return $u->id === $p->user_id;
    }

    public function delete(User $u, QrPage $p): bool
    {
        return $u->id === $p->user_id;
    }
}
