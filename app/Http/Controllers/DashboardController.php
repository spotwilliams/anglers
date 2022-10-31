<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $users = User::query()
            ->select(['id', 'name', 'club_id'])
            ->with(['club'])
            ->visibleTo(Auth::user())
            ->withIsFriendOfUser(Auth::user())
            ->orderByFriendsFirst(Auth::user())
            ->withLastTrip();

        return view('dashboard', ['users' => $users->paginate()]);
    }
}