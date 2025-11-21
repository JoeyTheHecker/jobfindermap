<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobListing;
class JobListingController extends Controller
{
    public function index()
    {
        return JobListing::select('id','title','company','city','province','region','lat','lng')
            ->get();
    }

}
