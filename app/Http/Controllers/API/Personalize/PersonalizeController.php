<?php

namespace App\Http\Controllers\API\Personalize;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PersonalizeController extends Controller
{
    //
    public function update(Request $request){
        $request->validate([
            'preferred_source' => 'required',
            'preferred_category' => 'required',
            'preferred_author' => 'required',
        ]);
        
        $user = User::find(auth()->user()->id);
        $user->preferred_source = $request->input('preferred_source');
        $user->preferred_category = $request->input('preferred_category');
        $user->preferred_author = $request->input('preferred_author');
        $user->save();
        return response()->json([
            'success' => true,
            'message' => 'Personalization updated successfully',
        ]);
    }
}
