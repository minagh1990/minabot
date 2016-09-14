<?php
set_time_limit(60);
date_default_timezone_set('Asia/Tehran');
header('Content-type: application/json');
    $update = json_decode(file_get_contents('php://input'),true);
    $message = $update["message"];
    $telegram_id = $message['from']['id'];
    $userInput = $message['text'];
    if(  $userInput=="/start"){
    $replyText = 'سلام به بازی حدس اعداد خوش آمدید.لطفا یک عدد از بین یک تا هزار انتخاب کنید و yesرا بزنید';
    $reply = [
        'method' => 'sendMessage',
        'chat_id' => $message['chat']['id'],
        'text' => $replyText,
    ];
    echo json_encode($reply);
    }
    
 if($userInput == "/yes") {
     $replyText2='آیا عدد انتخابی شما از پانصد بیشتر است؟';
    $reply2 = [
        'method' => 'sendMessage',
        'chat_id' => $message['chat']['id'],
        'text' => $replyText2,
    ];
     echo json_encode($reply2);
}
