<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;

class FormController extends Controller
{
    public function index()
    {
        if (!SiteHelper::moduleEnabled('forms')) {
            abort(404);
        }

        return view('account.forms.index');
    }
}
