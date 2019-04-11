<?php

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;

class CardConversation extends Conversation {
  public function askPhoto()
  {
      $this->askForImages('Please upload an image.', function ($images) {

      });
  }

  public function run() {
    $this->askPhoto();
  }
}
