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
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\Drivers\Facebook\Extensions\Element;
use BotMan\Drivers\Facebook\Extensions\ElementButton;
use BotMan\Drivers\Facebook\Extensions\GenericTemplate;

use Grafika\Grafika;

require_once './vendor/autoload.php';
require_once './src/autoloader.php';
require_once './conversation/CardConversation.php';

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

function saveImage($srcUrl, $distUrl, $fileName) {
  $urlToImage = $srcUrl;
  $completeSaveLoc = $distUrl . $fileName;
  file_put_contents($completeSaveLoc, file_get_contents($urlToImage));
}

function imageSynthesis($srcTitle, $srcText, $srcImage, $userId, $bot) {
  $editor = Grafika::createEditor();
  $editor->open($image1, 'card_materials/background.png');
  $editor->open($image2, $srcImage);
  $editor->blend($image1, $image2 , 'normal', 0.9, 'center');
  $editor->text($image1, $srcText, 36, 200, 200, null, '', 0);
  $editor->save($image1, 'users_data/mothersCard_' . $userId . '.png');

  $bot->reply(GenericTemplate::create()
    ->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)
    ->addElements([
      Element::create('印刷廠印製完成...')
        ->subtitle('前往卡片網頁')
        ->image('https://nmdap.udn.com.tw/newmedia/mothers_day_bot/users_data/mothersCard_' . $userId . '.png')
        ->addButton(ElementButton::create('visit')
            ->url('https://nmdap.udn.com.tw/newmedia/mothers_day_bot/#' . $userId)
        )
    ])
  );
}

$botman->hears('make a card', function(BotMan $bot) {
  $bot->reply('請輸入要對愛人說的話，開頭為...');
});

// 接收使用者輸入文字  
$botman->hears('to mom {text}', function(BotMan $bot, $text) {
  // $bot->reply($text);
  $bot->userStorage()->save([
    'text' => $text
  ]);
  $bot->reply(Question::create('文字確定?')->addButtons([
    Button::create('是')->value('textYes'),
    Button::create('否')->value('textNo'),
  ]));
});

$botman->hears('textYes', function(BotMan $bot) {
  $bot->userStorage()->save([
    'textFlag' => 'true'
  ]);
  $bot->reply('請輸入和愛人的合照');
});

$botman->hears('textNo', function(BotMan $bot) {
  $bot->userStorage()->save([
    'textFlag' => 'false',
    'text' => ''
  ]);

  $bot->reply('請重新輸入想對愛人說的話');
});

// 接收使用者輸入圖片
$botman->receivesImages(function(BotMan $bot, $images) {
  foreach ($images as $image) {
    $url=$image->getUrl();
    // $title=$image->getTitle();
    // $bot->reply($url);
    $bot->userStorage()->save([
      'image' => $url
    ]);
    $bot->reply(Question::create('圖片確定?')->addButtons([
      Button::create('是')->value('imageYes'),
      Button::create('否')->value('imageNo'),
    ]));
  }
});

$botman->hears('imageYes', function(BotMan $bot) {
  $bot->userStorage()->save([
    'imageFlag' => 'true'
  ]);

  $imageUrl = $bot->userStorage()->get('image');
  saveImage($imageUrl, 'users_data/', 'userImage.png');

  $bot->reply(Question::create('確定開始印製卡片?')->addButtons([
    Button::create('是')->value('allYes'),
    Button::create('否')->value('allNo'),
  ]));
});

$botman->hears('imageNo', function(BotMan $bot) {
  $bot->userStorage()->save([
    'imageFlag' => 'false',
    'image' => ''
  ]);
  $bot->reply('請重新輸入和愛人的合照');
});

// 全部完成與否
$botman->hears('allYes', function (BotMan $bot) {
  $textContent = $bot->userStorage()->get('text');
  $userId = rand(0,100);
  imageSynthesis('Title', $textContent, 'users_data/userImage.png', $userId, $bot);
  // 寫進資料庫，不再寫入文字
});

$botman->hears('allNo', function (BotMan $bot) {
  $bot->reply('重新開始，請輸入對愛人說的話');
  $bot->userStorage()->save([
    'textFlag' => 'false',
    'imageFlag' => 'false'
  ]);
  // 寫進資料庫，不再寫入文字
});







// Start listening
$botman->listen();