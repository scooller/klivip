<?php

namespace App\Http\Controllers\Front;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var Site $site */
        $site = $request->attributes->get('currentSite');
        $customer = Auth::guard('customer')->user();

        return Inertia::render('Schedule', [
            'site' => [
                'name' => $site->name,
                'slug' => $site->slug,
                'logo' => $site->logo ? asset('storage/'.$site->logo) : null,
                'content' => $site->content,
                'address' => $site->address,
                'opening_hours' => $site->opening_hours,
                'links' => $site->links,
            ],
            'auth' => [
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'role' => UserRole::User->value,
                ] : null,
            ],
            'calendarDays' => [],
        ]);
    }
}
