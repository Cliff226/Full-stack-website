<?php

function verifyRecaptcha(string $token): bool
{
    $secretKey = "6LcqeCEsAAAAAELjt3cutnkYfzLKPpE_4k3oi6Si";
    

    // Call Google API
    $response = file_get_contents(
        "https://www.google.com/recaptcha/api/siteverify?secret={$secretKey}&response={$token}"
    );

    $result = json_decode($response, true);

    // Return TRUE only if verification is successful
    return isset($result['success']) && $result['success'] === true;
}