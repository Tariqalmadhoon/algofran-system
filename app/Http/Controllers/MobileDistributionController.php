<?php

namespace App\Http\Controllers;

use App\Services\AndroidReleaseService;
use Illuminate\Contracts\View\View;

class MobileDistributionController extends Controller
{
    public function __invoke(AndroidReleaseService $androidReleases): View
    {
        $release = $androidReleases->current();

        return view('mobile-distribution.index', [
            'release' => $release,
            'teacherAppUrl' => route('teacher.mobile.app'),
            'downloadUrl' => $release === null ? null : route('teacher.mobile.app.download', [
                'versionCode' => $release['version_code'],
            ]),
        ]);
    }
}
