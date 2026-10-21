<?php

namespace PHPinnacle\Casus\Http\Controllers;

use Illuminate\Http\Response;
use PHPinnacle\Casus\Http\Requests\ReportFrontendErrorRequest;
use PHPinnacle\Casus\Services\ExceptionReporter;

class ReportFrontendErrorController
{
    public function __invoke(ReportFrontendErrorRequest $request, ExceptionReporter $reporter): Response
    {
        $reporter->reportFrontend($request->error(), $request);

        return response()->noContent();
    }
}
