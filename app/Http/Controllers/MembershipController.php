<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BeatMembership;

class MembershipController extends Controller
{
    public function GetMembership(){
        $memberships = BeatMembership::all();
        return response()->json($memberships);
    }

    
}
