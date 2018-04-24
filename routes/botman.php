<?php
use App\Http\Controllers\BotManController;

$botman = resolve('botman');

$botman->hears('Hi', function ($bot) {
    $bot->reply('Hello!');
});
$botman->hears('How are you', function ($bot) {
    $bot->reply('Doing great! Yourself?');
});
$botman->hears('.*great.*', function ($bot) {
    $bot->reply('I am glad to hear that!');
    
});
$botman->hears('.*joke.*', function ($bot) { 
    $context = stream_context_create([
        'http' => [
            'method' => "GET",
            'header' => "Accept: text/plain"
        ]
    ]);

    $bot->reply(file_get_contents('https://icanhazdadjoke.com', false, $context));
    //$bot->reply('Why did Mozart get rid of his chickens?... Because they kept saying Bach, Bach!');
});
$botman->hears('.*bye.*', function ($bot) {
    $bot->reply('TTFN!');
});

$botman->hears('Start conversation', BotManController::class.'@startConversation');

$botman->hears('My name is {name}', function ($bot, $name) {
    $bot->reply('Hello, ' . ucwords($name));
});

$botman->hears('.*ebay.*', function($bot) {
    $service = new DTS\eBaySDK\Trading\Services\TradingService([
        'apiVersion' => '951', 'siteId' => 1
    ]);

    $creds = new DTS\eBaySDK\Trading\Types\CustomSecurityHeaderType();
    $creds->eBayAuthToken = env('EBAY_AUTH_TOKEN');

    $request = new DTS\eBaySDK\Trading\Types\GetSellerListRequestType();
    $request->RequesterCredentials = $creds;
    $request->StartTimeFrom = new DateTime('-3 months');
    $request->StartTimeTo = new DateTime();

    $response = $service->getSellerList($request)->toArray();
    $listings = array_get($response, 'ItemArray.Item');
    
    $output = "You have " . count($listings) . " " . str_plural("listing", count($listings)) . "." ;

    foreach($listings AS $listing) {
        $request = new DTS\eBaySDK\Trading\Types\GetItemRequestType();
        $request->ItemID = $listing['ItemID'];
        $request->RequesterCredentials = $creds;
        $request->IncludeWatchCount = true;

        $response = $service->getItem($request)->toArray();
        $item = $response['Item'];
        $status = $item['SellingStatus'];
        $bidcount = $status['BidCount'];
        $views = $item['HitCount'];
        $watchers = $item['WatchCount'];

        $output .= "\n\n" . $item['Title'] . " has a current price of $" . $status['CurrentPrice']['value'] . ". So far there have been " . $bidcount . " " . str_plural('bid', $bidcount) . ", " . $watchers . " " . str_plural('watcher', $watchers) . ", and " . $views . " " . str_plural('view', $views) . ". ";
    }

    $bot->reply($output);
});

$botman->hears('.*inbox.*', function($bot) {
    $server = new Ddeboer\Imap\Server('imap.gmail.com');
    $connection = $server->authenticate(env('GMAIL_USERNAME'), env('GMAIL_PASSWORD'));

    $mailbox = $connection->getMailbox('INBOX');
    $status = (array) $mailbox->getStatus();

    $bot->reply("You have " . $status['unseen'] . " new " . str_plural('email', $status['unseen']) . " in your inbox");
});