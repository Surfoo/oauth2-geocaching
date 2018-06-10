<?php 

require dirname(__DIR__) . '/vendor/autoload.php';

use League\OAuth2\Client\Provider\Geocaching;
use League\OAuth2\Client\Provider\Exception\GeocachingIdentityProviderException;

define('CLIENT_ID_PRODUCTION',     '');
define('CLIENT_SECRET_PRODUCTION', '');

define('CLIENT_ID_STAGING',     '');
define('CLIENT_SECRET_STAGING', '');

session_start();

// STAGING
$provider = new Geocaching([
    'clientId'       => CLIENT_ID_STAGING,
    'clientSecret'   => CLIENT_SECRET_STAGING,
    'response_type'  => 'code',
    'scope'          => '*',
    'redirectUri'    => 'http://localhost:8000',
    'environment'    => 'staging'
]);

// PRODUCTION
// $provider = new League\OAuth2\Client\Provider\Geocaching([
//     'clientId'       => CLIENT_ID_PRODUCTION,
//     'clientSecret'   => CLIENT_SECRET_PRODUCTION,
//     'response_type'  => 'code',
//     'scope'          => '*',
//     'redirectUri'    => 'http://localhost:8000',
//     'environment'    => 'production'
// ]);


if (!isset($_GET['code'])) {
    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {
    // Try to get an access token (using the authorization code grant)

    try {
        $token = $provider->getAccessToken('authorization_code', [
            'code'         => $_GET['code']
        ]);
    } catch(GeocachingIdentityProviderException $e) {
        exit($e->getMessage());
    }
    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        // printf('Hello %s!', $user->getReferenceCode());
        echo "<pre>";
        print_r($user->toArray());
        echo "</pre>";

    } catch (Exception $e) {

        // Failed to get user details
        exit($e->getMessage());
    }

    // Use this to interact with an API on the users behalf
    // echo "Token: " . $token->getToken();
}
