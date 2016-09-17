<?php
namespace Longman\TelegramBot\Commands\SystemCommands;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\DBHelper;
use Longman\TelegramBot\Entities\InlineKeyboardMarkup;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
require ('DBHelper.php');
class CallbackqueryCommand extends SystemCommand
{
    private $message_prefix = 'Tic-Tac-Toe' . "\n\n";
    private $strings = [
        'X' => '❌',
        'O' => '⭕️',
    ];
    private $empty_board = [
        ['', '', ''],
        ['', '', ''],
        ['', '', '']
    ];
    private $query_id;
    private $message_id;
    public function execute()
    {
        $update = $this->getUpdate();
        $callback_query = $update->getCallbackQuery();
        if ($callback_query) {
            $user_id = $callback_query->getFrom()->getId();
            $this->query_id = $callback_query->getId();
            $this->message_id = $callback_query->getInlineMessageId();
            $text = $callback_query->getData();
            $text = explode('_', $text);
            $command = $text[0];
            $gameId = $text[1];
            $timestamp = date('Y-m-d H:i:s', time());
            $this_user_fullname = $callback_query->getFrom()->getFirstName();
            if (!empty($callback_query->getFrom()->getLastName())) {
                $this_user_fullname .= ' ' . $callback_query->getFrom()->getLastName();
            }
            if (is_numeric($gameId)) {
                $query_raw = DBHelper::query('SELECT * FROM `tictactoe` WHERE `id` = ' . $gameId);
            }
            if (!empty($gameId) && count($query_raw) == 1) {
                $query = $query_raw[0];
                $data = json_decode($query['data'], true);
                $host_id = $query['host_id'];
                $guest_id = $query['guest_id'];
                $host_fullname = '';
                $guest_fullname = '';
                if ($host_id) {
                    $host_user = DBHelper::query('SELECT * FROM `user` WHERE `id` = ' . $host_id);
                    if ($host_user) {
                        $host_fullname = $host_user[0]['first_name'];
                        if (!empty($host_user[0]['last_name'])) {
                            $host_fullname .= ' ' . $host_user[0]['last_name'];
                        }
                    }
                }
                if ($guest_id) {
                    $guest_user = DBHelper::query('SELECT * FROM `user` WHERE `id` = ' . $guest_id);
                    if ($guest_user) {
                        $guest_fullname = $guest_user[0]['first_name'];
                        if (!empty($guest_user[0]['last_name'])) {
                            $guest_fullname .= ' ' . $guest_user[0]['last_name'];
                        }
                    }
                }
                if ($command == 'join') {
                    if ($user_id == $host_id && !(is_array($this->telegram->getAdminList()) && in_array($user_id, $this->telegram->getAdminList()))) {
                        return $this->answerCallback();
                    }
                    if (empty($host_id)) {
                        $result = DBHelper::query('UPDATE `tictactoe` SET `host_id` = ' . $user_id . ', `updated_at` = \'' . $timestamp . '\' WHERE `id` = ' . $gameId);
                        if ($result) {
                            $this->editMessage(
                                $this_user_fullname . ' is waiting for opponent to join...' . "\n" . 'Press \'Join\' button to join.',
                                $this->createInlineKeyboard('lobby', $gameId)
                            );
                        }
                    } elseif (empty($guest_id)) {
                        $result = DBHelper::query('UPDATE `tictactoe` SET `guest_id` = ' . $user_id . ', `updated_at` = \'' . $timestamp . '\' WHERE `id` = ' . $gameId);
                        if ($result) {
                            $move = $this->initalizeGame($gameId, $data);
                            if ($move) {
                                $move = str_replace('X', $this->strings['X'], $move);
                                $move = str_replace('O', $this->strings['O'], $move);
                                $this->editMessage(
                                    $host_fullname . ' (' . $this->strings['X'] . ') ' . '⚔ ' . $guest_fullname . ' (' . $this->strings['O'] . ')' . "\n\n" . 'Current turn:' . ' ' . $move,
                                    $this->createInlineKeyboard('game', $gameId, $this->empty_board)
                                );
                            }
                        }
                    }
                    return $this->answerCallback();
                } elseif ($command == 'quit') {
                    if ($user_id == $guest_id) {
                        $result = DBHelper::query('UPDATE `tictactoe` SET `guest_id` = null, `updated_at` = \'' . $timestamp . '\' WHERE `id` = ' . $gameId);
                        if ($result) {
                            $this->editMessage(
                                $host_fullname . ' is waiting for opponent to join...' . "\n" . 'Press \'Join\' button to join.',
                                $this->createInlineKeyboard('lobby', $gameId)
                            );
                            return $this->answerCallback();
                        }
                    } elseif ($user_id == $host_id) {
                        if (empty($guest_id)) {
                            $result = DBHelper::query('UPDATE `tictactoe` SET `host_id` = null, `updated_at` = \'' . $timestamp . '\' WHERE `id` = ' . $gameId);
                            if ($result) {
                                $this->editMessage(
                                    'This game session is empty.',
                                    $this->createInlineKeyboard('empty', $gameId)
                                );
                            }
                        } else {
                            $result = DBHelper::query('UPDATE `tictactoe` SET `host_id` = ' . $guest_id . ', `guest_id` = null , `data` = \'' . json_encode($data) . '\', `updated_at` = \'' . $timestamp . '\' WHERE `id` = ' . $gameId);
                            if ($result) {
                                $this->editMessage(
                                    $guest_fullname . ' is waiting for opponent to join...' . "\n" . 'Press \'Join\' button to join.',
                                    $this->createInlineKeyboard('lobby', $gameId)
                                );
                            }
                        }
                    }
                    return $this->answerCallback();
                } elseif ($command == 'start') {
                    if ($user_id == $host_id) {
                        $move = $this->initalizeGame($gameId, $data);
                        if ($move) {
                            $move = str_replace('X', $this->strings['X'], $move);
                            $move = str_replace('O', $this->strings['O'], $move);
                            $this->editMessage(
                                $host_fullname . ' (' . $this->strings['X'] . ') ' . '⚔ ' . $guest_fullname . ' (' . $this->strings['O'] . ')' . "\n\n" . 'Current turn:' . ' ' . $move,
                                $this->createInlineKeyboard('game', $gameId, $this->empty_board)
                            );
                        }
                    } elseif ($user_id == $guest_id) {
                        return $this->answerCallback('You\'re not the host!', true);
                    }
                    return $this->answerCallback();
                }
                // Handle the game
                if ($user_id == $host_id || $user_id == $guest_id) {
                    if ($data['current_turn'] == 'E') {
                        return $this->answerCallback();
                    }
                    if (!($user_id == $host_id && $data['current_turn'] == 'X') && !($user_id == $guest_id && $data['current_turn'] == 'O')) {
                        return $this->answerCallback('It\'s not your turn!', true);
                    }
                    $isValidMove = false;
                    $gameEnded = false;
                    $gameResult = '';
                    $currentMove = '';
                    if ($data['board'][$command[0]][$command[1]] == '') {
                        $isValidMove = true;
                    }
                    if ($isValidMove) {
                        if ($user_id == $host_id && $data['current_turn'] == 'X') {
                            $data['board'][$command[0]][$command[1]] = 'X';
                            $data['current_turn'] = 'O';
                        } elseif ($user_id == $guest_id && $data['current_turn'] == 'O') {
                            $data['board'][$command[0]][$command[1]] = 'O';
                            $data['current_turn'] = 'X';
                        }
                    } else {
                        return $this->answerCallback('Invalid move!', true);
                    }
                    $isOver = $this->isGameOver($data['board']);
                    if (in_array($isOver, ['X', 'O', 'T'])) {
                        if ($isOver == 'X') {
                            $gameResult = "\n\n" . $host_fullname . ' has won!';
                            $winner = 'X';
                        } elseif ($isOver == 'O') {
                            $gameResult = "\n\n" . $guest_fullname . ' has won!';
                            $winner = 'O';
                        } else {
                            $gameResult = "\n\n" . 'Game ended with a draw!';
                        }
                        $data['current_turn'] = 'E';
                        $gameEnded = true;
                    }
                    if (!$gameEnded) {
                        $move = $data['current_turn'];
                        $move = str_replace('X', $this->strings['X'], $move);
                        $move = str_replace('O', $this->strings['O'], $move);
                        $currentMove = "\n\n" . 'Current turn:' . ' ' . $move;
                    }
                    $result = DBHelper::query('UPDATE `tictactoe` SET `data` = \'' . json_encode($data) . '\', `updated_at` = \'' . $timestamp . '\' WHERE `id` = ' . $gameId);
                    if ($result) {
                        $this->editMessage(
                            $host_fullname . ' (' . $this->strings['X'] . ') ' . '⚔ ' . $guest_fullname . ' (' . $this->strings['O'] . ')' . $gameResult . $currentMove,
                            $this->createInlineKeyboard('game', $gameId, $data['board'], $gameEnded, $winner)
                        );
                    }
                }
                return $this->answerCallback();
            } else {
                if ($command == 'new') {
                    $result = DBHelper::query('INSERT INTO `tictactoe` (`host_id`, `created_at`, `updated_at`) VALUES (' . $user_id . ', \'' . $timestamp . '\', \'' . $timestamp . '\')');
                    if ($result) {
                        $gameId = DBHelper::lastInsertId();
                        $this->editMessage(
                            $this_user_fullname . ' is waiting for opponent to join...' . "\n" . 'Press \'Join\' button to join.',
                            $this->createInlineKeyboard('lobby', $gameId)
                        );
                    }
                } else {
                    $this->editMessage(
                        'This game session is no longer available.',
                        $this->createInlineKeyboard('invalid', $gameId)
                    );
                }
                return $this->answerCallback();
            }
        }
        return Request::emptyResponse();
    }
    private function editMessage($text, $reply_markup)
    {
        $data = [];
        $data['inline_message_id'] = $this->message_id;
        $data['text'] = $this->message_prefix . $text;
        $data['reply_markup'] = $reply_markup;
        return Request::editMessageText($data);
    }
    private function answerCallback($text = '', $notify = false)
    {
        $data = [];
        $data['callback_query_id'] = $this->query_id;
        $data['text'] = $text;
        if ($notify) {
            $data['show_alert'] = true;
        }
        return Request::answerCallbackQuery($data);
    }
    private function initalizeGame($id, $data)
    {
        if (!empty($data['start_turn']) && $data['start_turn'] == 'X') {
            $move = 'O';
        } else {
            $move = 'X';
        }
        $data['board'] = $this->empty_board;
        $data['start_turn'] = $move;
        $data['current_turn'] = $move;
        $result = DBHelper::query('UPDATE `tictactoe` SET `data` = \'' . json_encode($data) . '\', `updated_at` = \'' . date('Y-m-d H:i:s', time()) . '\' WHERE `id` = ' . $id);
        if ($result) {
            return $move;
        }
        return false;
    }
    private function createInlineKeyboard($menu = '', $id = null, $board = null, $gameIsOver = false, $winner = null)
    {
        $inline_keyboard = [];
        if ($menu == 'game' && is_array($board)) {
            for ($x = 0; $x < 3; $x++) {
                $tmp_array = [];
                for ($y = 0; $y < 3; $y++) {
                    if ($board[$x][$y] == 'X' || $board[$x][$y] == 'O') {
                        $field = $this->strings[$board[$x][$y]];
                    } else {
                        $field = ' ';
                    }
                    array_push(
                        $tmp_array,
                        new InlineKeyboardButton(
                            [
                                'text' => ' '.$field,
                                'callback_data' => $x . $y . '_' . $id
                            ]
                        )
                    );
                }
                $inline_keyboard[] = $tmp_array;
            }
            $inline_keyboard[] = [
                new InlineKeyboardButton(
                    [
                        'text' => 'Quit',
                        'callback_data' => "quit_" . $id
                    ]
                ),
                new InlineKeyboardButton([
                    'text' => 'New',
                    'callback_data' => "start_" . $id
                ])
            ];
        } elseif ($menu == 'lobby') {
            $inline_keyboard[] = [
                new InlineKeyboardButton(
                    [
                        'text' => 'Quit',
                        'callback_data' => "quit_" . $id
                    ]
                ),
                new InlineKeyboardButton(
                    [
                        'text' => 'Join',
                        'callback_data' => "join_" . $id
                    ]
                )
            ];
        } elseif ($menu == 'empty') {
            $inline_keyboard[] = [
                new InlineKeyboardButton(
                    [
                        'text' => 'Join',
                        'callback_data' => "join_" . $id
                    ]
                )
            ];
        } elseif ($menu == 'invalid') {
            $inline_keyboard[] = [
                new InlineKeyboardButton(
                    [
                        'text' => 'New',
                        'callback_data' => "new"
                    ]
                )
            ];
        }
        $inline_keyboard_markup = new InlineKeyboardMarkup(['inline_keyboard' => $inline_keyboard]);
        return $inline_keyboard_markup;
    }
    private function isGameOver($board)
    {
        $empty = 0;
        for ($x = 0; $x < 3; $x++) {
            for ($y = 0; $y < 3; $y++) {
                if ($board[$x][$y] == '') {
                    $empty++;
                }
                if ($board[$x][$y] != '' && $board[$x][$y] == $board[$x][$y + 1] && $board[$x][$y] == $board[$x][$y + 2]) {
                    return $board[$x][$y];
                }
                if ($board[$x][$y] != '' && $board[$x][$y] == $board[$x + 1][$y] && $board[$x][$y] == $board[$x + 2][$y]) {
                    return $board[$x][$y];
                }
                if ($board[$x][$y] != '' && $board[$x][$y] == $board[$x + 1][$y + 1] && $board[$x][$y] == $board[$x + 2][$y + 2]) {
                    return $board[$x][$y];
                }
                if ($board[$x][$y] != '' && $board[$x][$y] == $board[$x - 1][$y + 1] && $board[$x][$y] == $board[$x - 2][$y + 2]) {
                    return $board[$x][$y];
                }
            }
        }
        if ($empty == 0) {
            return 'T';
        }
    }
}
