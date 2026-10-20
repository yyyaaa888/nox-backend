<?php

namespace App\Admin\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AjaxController extends Controller
{
    public function users(Request $request)
    {
        $q = $request->get('q');
        if ($q) {
            if (is_numeric($q)) {
                $users = User::where('id', (int) $q)->paginate(null, ['id', 'name as text']);
            } elseif (strlen($q) >= 40) {
                $users = User::where('address', $q)->paginate(null, ['id', 'name as text']);
            } else {
                $users = User::where('name', 'like', '%'.$q.'%')->paginate(null, ['id', 'name as text']);
            }

            return response()->json($users);
        } else {
            return response()->json([]);
        }
    }
}
