<?php

namespace App\Http\Controllers;

use App\Models\Drives;
use Illuminate\Http\Request;

class DrivesController extends Controller
{
    public function getDrives(){
        return Drives::all();
    }
}
