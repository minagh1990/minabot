<?php
set_time_limit(60);
date_default_timezone_set('Asia/Tehran');
header('Content-type: application/json');
    $update = json_decode(file_get_contents('php://input'), true);
    $message = $update["message"];
    $telegram_id = $message['from']['id'];
    $userInput = $message['text'];
    $replyText = 'سلام به بازی حدس اعداد خوش آمدید\nلطفا یک عدد از بین 1تا20انتخاب کنید و کامنت yes را انتخاب کنید';
    $reply = [
        'method' => 'sendMessage',
        'chat_id' => $message['chat']['id'],
        'text' => $replyText,
    ];
	if($message['chat']['id']=="yes"){
		$replyText='آیا عدد شما کمتر از 10است؟'
	}
	else{
		$replyText ='سلام'
	}
    echo json_encode($reply);
?>
