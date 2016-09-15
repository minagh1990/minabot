<?php
set_time_limit(60);
date_default_timezone_set('Asia/Tehran');
header('Content-type: application/json');
$update = $_req;
$message = $update["message"];
$telegram_id = $message['from']['id'];
$userInput = $message['text'];
$replyText = '';
session_id('telegram00' . $telegram_id);
session_start();
// the main game process
switch ($userInput) {
    case '/start':
        $replyText = 'لطفا یک عدد بین یک تا هزار انتخاب کنید
        بعد من سوال می پرسم و سعی می کنم بفهمم عدد انتخابی شما چیه!';
        $keyboard = ['keyboard' => [['انتخاب کردم']],
                     'resize_keyboard' => true,
                     'one_time_keyboard' => true];
        $_SESSION['game_min'] = 0;
        $_SESSION['game_max'] = 1000;
        //first selected number is chosen randomly to add some salt to the game, but limited to 100 and 900 for a faster guess
        $_SESSION['game_selected'] = mt_rand(100,900);
        $_SESSION['game_started'] = true;
        break;
    case 'خودشه':
        $replyText = 'بود'.$_SESSION['game_selected'].'پس عدد شما ';
        $keyboard = ['keyboard' => [['/start']],
                     'resize_keyboard' => true,
                     'one_time_keyboard' => true];
        $_SESSION['game_started'] = false;
        break;
    case 'بیشتره':
        $_SESSION['game_min'] = $_SESSION['game_selected'];
        $_SESSION['game_selected'] = ($_SESSION['game_max']+$_SESSION['game_min'])/2;
        $replyText = 'عدد انتخابی شما 
        '.$_SESSION['game_selected'].'
        هستش؟';

        $keyboard = ['keyboard' => [['کمتره','بیشتره','خودشه']],
                     'resize_keyboard' => true,
                     'one_time_keyboard' => true];
        break;
    case 'کمتره':
        $_SESSION['game_max'] = $_SESSION['game_selected'];
        $_SESSION['game_selected'] = ($_SESSION['game_max']+$_SESSION['game_min'])/2;
        $replyText = 'عدد انتخابی شما 
        '.$_SESSION['game_selected'].'
        هستش؟';

        $keyboard = ['keyboard' => [['کمتره','بیشتره','خودشه']],
                     'resize_keyboard' => true,
                     'one_time_keyboard' => true];

        break;
    case 'انتخاب کردم':
        $replyText = 'عدد انتخابی شما 
        '.$_SESSION['game_selected'].'
        هستش؟';

        $keyboard = ['keyboard' => [['کمتره','بیشتره','خودشه']],
                     'resize_keyboard' => true,
                     'one_time_keyboard' => true];
        $_SESSION['game_min'] = 0;
        $_SESSION['game_max'] = 1000;
        break;
    default:
        $replyText = 'چیزی که فرستادین رو متوجه نشدم لطفا یکبار دیگه ارسال کنید';
        if (isset($_SESSION['game_started']) && $_SESSION['game_started']) {
            $keyboard = ['keyboard' => [['کمتره','بیشتره','خودشه']],
                         'resize_keyboard' => true,
                         'one_time_keyboard' => true];
        }else{
            $keyboard = ['keyboard' => [['/start']],
                         'resize_keyboard' => true,
                         'one_time_keyboard' => true];
        }

        break;
}


$reply = ['method' => 'sendMessage',
          'chat_id' => $message['chat']['id'],
          'text' => $replyText,
          'reply_markup' => $keyboard,
          'disable_web_page_preview' => true];

echo json_encode($reply);