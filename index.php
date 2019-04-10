<?php
// Database config
// $servername = 'localhost';
// $username = 'newmedia';
// $password = 'newmedia';
// // $username = 'chatbot_user';
// // $password = '';
// $dbname = 'mothers_day_bot';

// $conn = new mysqli($servername, $username, $password, $dbname);
// mysqli_query($conn, "SET NAMES UTF8");

// if($conn->connect_error) {
//   die('Connection failed: ' . $conn->connect_error);
// }

// $sql = "INSERT INTO card (user_name, user_id, image, text) VALUES ('" . $這裡放變數 . "', '" . $這裡放變數 . "', '" . $這裡放變數 . "' . '" . $這裡放變數 . "')";
// $conn->query($sql);
// $conn->close();


use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;

use Grafika\Grafika;

require_once './vendor/autoload.php';
require_once './src/autoloader.php';

$config = [
    // Your driver-specific configuration
    'facebook' => [
      'token' => 'EAAgFGCRdh0YBAO6YOEFzHACkJPmpWCXY60AgtsoBO3cotn0C32fklDurz6nscGZCvS83BasSXrZAtjWjGXhq86wsMV2QhsoN4XYlucR6uMxufZBCEIUOdL3ZB5TPhaAq3EUAkhmXnZCPSkVXVWDoQnz1wBjeo69NvOKfwQJn5nQZDZD',
      'app_secret' => 'ee950085cfeacdd271ebaad5be3672aa',
      'verification'=>'happymothersdayudnforyou',
    ]
];

// Load the driver(s) you want to use
DriverManager::loadDriver(\BotMan\Drivers\Facebook\FacebookDriver::class);
DriverManager::loadDriver(\BotMan\Drivers\Facebook\FacebookImageDriver::class);

// Create an instance
$botman = BotManFactory::create($config);
$textBoolean = 0;
$imageBoolean = 0;

// $botman->say('來寫母親節卡片吧');
// $botman->hears('hello', function (BotMan $bot) {
//   $bot->reply('hi');
// });




$botman->hears('to mom {text}', function(BotMan $bot, $text) {
  // $bot->reply($text);
  $bot->reply(Question::create('確定給媽媽的話?')->addButtons([
    Button::create('Yes')->value('text yes'),
    Button::create('No')->value('text no'),
  ]));

  // $bot->startConversation(new CardConversation);
});

$botman->receivesImages(function(BotMan $bot, $images) {
  foreach ($images as $image) {
      $url=$image->getUrl();
      $title=$image->getTitle();
      // $bot->reply($url);
      $editor = Grafika::createEditor();
      $editor->open($image1 , '1.png');
      $editor->resizeFit($image1 , 200 , 200);
      $editor->save($image1 , '2.png');
  }

  $bot->reply(Question::create('確定卡片的圖片?')->addButtons([
    Button::create('Yes')->value('image yes'),
    Button::create('No')->value('image no'),
  ]));
});

$botman->hears('text yes', function (BotMan $bot) {
  $textBoolean = 1;
  // 如果圖文都上傳，詢問是否要開始製作卡片
  if ($textBoolean = 1 && $imageBoolean = 1) {
    $bot->reply(Question::create('要開始製作卡片了嗎?')->addButtons([
      Button::create('Yes')->value('all yes'),
      Button::create('No')->value('all no'),
    ]));
  } else {
    $bot->reply('字寫完囉');
  }

  // 寫進資料庫，不再寫入文字
});

$botman->hears('image yes', function (BotMan $bot) {
  $imageBoolean = 1;
  // 如果圖文都上傳，詢問是否要開始製作卡片
  if ($textBoolean = 1 && $imageBoolean = 1) {
    $bot->reply(Question::create('要開始製作卡片了嗎?')->addButtons([
      Button::create('Yes')->value('all yes'),
      Button::create('No')->value('all no'),
    ]));
  } else {
    $bot->reply('照片選好囉');
  }

  // 寫進資料庫，不再寫入圖片
});

$botman->hears('all yes', function (BotMan $bot) {
  $bot->reply('全部輸入完成');
  $textBoolean = 0;
  $imageBoolean = 0;
  // 寫進資料庫，不再寫入文字
});

// Start listening
$botman->listen();
