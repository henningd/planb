<?php

use App\Support\Mobile\OnboardingQrCode;

test('it builds an embeddable svg data uri', function () {
    $uri = OnboardingQrCode::dataUri('{"url":"https://notfallhandbuch.eu","key":"","email":"a@b.de","code":"ABCD2345"}');

    expect($uri)->toStartWith('data:image/svg+xml');
});
