<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function update(User $user, array $data): User
    {
        if (isset($data['avatar'])) {

            if ($user->avatar) {
                Storage::disk('public')
                    ->delete($user->avatar);
            }

            $data['avatar'] = $data['avatar']
                ->store('avatars', 'public');
        }

        $user->update($data);

        return $user;
    }


    public function delete(User $user): void
    {
        if ($user->avatar) {
            Storage::disk('public')
                ->delete($user->avatar);
        }

        $user->delete();
    }
}