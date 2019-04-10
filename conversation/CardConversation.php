<?php

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Attachments\Image;

class CardConversation extends Conversation {
  protected $cardText;

  public function askForCardInfo() {
    $this->ask('請輸入祝福卡片圖片', function(Answer $answer) {

    });

    // $this->ask('Hello! What is your firstname?', function(Answer $answer) {
    //   // Save result
    //   $this->firstname = $answer->getText();

    //   $this->say('Nice to meet you '.$this->firstname);
    //   $this->askEmail();
    // });
  }

  public function returnCard() {
    $img = Image::url('https://nmdap.udn.com.tw/newmedia/mothers_day_bot/1.png');
    $message = OutgoingMessage::create()->withAttachment($img);
    $this->say($message);
  }

  public function run() {
    $this->askForCardInfo();
  }
}
