<?php
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
use Grafika\Color;

require_once './vendor/autoload.php';
require_once './src/autoloader.php';

$config = [
  // Your driver-specific configuration
  'facebook' => [
    'token' => 'EAAgFGCRdh0YBANc6ZCZCxwosG3y6XOTwfPiDQsyZAWUfZApHeIAj68P3eJ5eDhrLRVkg6UUgUZBNMjHWd178mmbxRsqQFhdYWX5upn7g3PmzROAN712xghXjh4ZCGK5rgTuO6MLicKZAjKlFgbaIvxV77WJgxoA9fPXPkSbkRgZAFQZDZD',
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
  $completeSaveLocation = $distUrl . $fileName;
  file_put_contents($completeSaveLocation, file_get_contents($srcUrl));
}

function imageSynthesis($srcTitle, $srcText, $srcImage, $fbUserName, $userId, $fbId, $float, $bot) {
  $distPath = $userId . '_' . $float . '.png';

  // 卡片合成
  $editor = Grafika::createEditor();
  $editor->open($image1, 'card_materials/background.png');
  $editor->open($image2, $srcImage);
  $editor->open($image3, 'card_materials/frame.png');

  // 圖片resize, resizeExact, resizeFill, resizeFit
  $editor->resizeFill($image2, 460, 360);
  
  // 圖片旋轉
  $editor->rotate($image2, 9, new Color('#ffffff'));
  
  // 圖片合成
  $editor->blend($image1, $image2, 'normal', 1, 'top-left', 25, 212);
  $editor->blend($image1, $image3 , 'normal', 1, 'top-left', 0, 212);
  
  // 標題
  $editor->text($image1, '媽咪，母親節快樂！', 44, 250, 90, null, 'fonts/ARMingB5Heavy.otf', 0);
  $editor->text($image1, '永遠青春美麗！', 44, 250, 160, null, 'fonts/ARMingB5Heavy.otf', 0);
  
  // 內文
  $editor->text($image1, '親愛的媽咪', 24, 45, 600, null, 'fonts/ARMingB5Medium.otf', 0);
  $editor->text($image1, '你是我的尤加利樹', 24, 45, 640, null, 'fonts/ARMingB5Medium.otf', 0);
  $editor->text($image1, '我是你的無尾熊', 24, 45, 680, null, 'fonts/ARMingB5Medium.otf', 0);
  $editor->text($image1, '乖女兒 娃娃', 24, 45, 740, null, 'fonts/ARMingB5Medium.otf', 0);

  $editor->save($image1, 'users_data/cards_dist/mothersCard_' . $distPath);

  // // 合成網頁大卡片

  // 寫進資料庫
  // Database config
  // $servername = 'localhost';
  // $username = 'newmedia';
  // $password = 'newmedia';
  // $dbname = 'mothers_day_bot';
  // $conn = new mysqli($servername, $username, $password, $dbname);
  // mysqli_query($conn, "SET NAMES UTF8");

  // if($conn->connect_error) {
  //   $bot->reply('haha');
  //   die('Connection failed: ' . $conn->connect_error);
  // }

  // $inputUserName = $fbUserName;
  // $inputUserId = $userId;
  // $inputText = 'empty';
  // $type = pathinfo($srcImage, PATHINFO_EXTENSION);
  // $data = file_get_contents($srcImage);
  // $inputImage = 'data:image/' . $type . ';base64,' . base64_encode($data);

  // $sql = "INSERT INTO card (user_name, user_id, image, text) VALUES ('" . $inputUserName . "', '" . $inputUserId . "', '" . $inputImage . "', '" . $inputText . "')";
  // $conn->query($sql);
  // $conn->close();

  // 回覆連結
  $bot->userStorage()->delete();
  $bot->reply(GenericTemplate::create()
    ->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)
    ->addElements([
      Element::create('印刷廠印製完成...')
        ->subtitle('前往卡片網頁')
        ->image('https://nmdap.udn.com.tw/newmedia/mothers_day_bot/users_data/mothersCard_' . $distPath)
        ->addButton(ElementButton::create('visit')
          ->url('https://nmdap.udn.com.tw/newmedia/mothers_day_bot/#' . $distPath)
        )
    ])
  );
}

function calcUsageCount($userId) {
  // $servername = 'localhost';
  // $username = 'newmedia';
  // $password = 'newmedia';
  // $dbname = 'mothers_day_bot';
  // $conn = new mysqli($servername, $username, $password, $dbname);
  // mysql_query($conn, "SET NAMES UTF8");

  // if($conn->connect_error) {
  //   $bot->reply('haha');
  //   die('Connection failed: ' . $conn->connect_error);
  // }

  // $query = "SELECT user_id FROM card WHERE user_id=" . $userId;
  // $result = mysql_query($query);

  // if (mysql_query($result) > 0) {
  //   // 使用者已使用過，修改次數，+1
  //   $currentCount = "SELECT count FROM usage_count WHERE user_id=" . $userId;
  //   $sql = "UPDATE usage_count SET count=" . $currentCount + 1 . "WHERE user_id=" . $userId;
  //   $conn->close();

  //   return $currentCount + 1;
  // } else {
  //   // 使用者第一次使用，新增欄位
  //   $sql = "INSERT INTO card (user_name, user_id, image, text) VALUES ('" . $inputUserName . "', '" . $inputUserId . "', '" . $inputImage . "', '" . $inputText . "')";
  //   $conn->query($sql);
  //   $conn->close();

  //   return 1;
  // }
}

function certifyReply($userStorage, $bot) {
  $titleFlag = $userStorage->get('titleFlag');
  $textFlag = $userStorage->get('textFlag');
  $imageFlag = $userStorage->get('imageFlag');

  if ($titleFlag != 1) {
    $bot->reply('請輸入標題');
  } else if ($textFlag != 1) {
    $bot->reply('請輸入內文');
  } else if ($imageFlag != 1) {
    $bot->reply('請輸入圖片');
  } else {
    $bot->reply(Question::create('是→開始印卡片 | 否→重新開始')->addButtons([
      Button::create('是')->value('allYes'),
      Button::create('否')->value('allNo'),
    ]));
  }
}

// 預設標題flag
// 預設文字flag
// 預設圖片flag
// $botman->hears('我要做卡片!', function(Botman $bot) {
//   $user = $bot->getUser();
//   $firstname = $user->getFirstName();
//   $lastname = $user->getLastName();
//   $id = $user->getId();
//   $bot->userStorage()->save([
//     'titleFlag' => 0,
//     'textFlag' => 0,
//     'imageFlag' => 0,
//   ]);
// });

// -----Step 1-----
// 是否使用預設標題
// $botman->hears('defaultText1', function(Botman $bot, $text) {
//   $bot->reply(Question::create('標題確定?')->addButtons([
//     Button::create('是')->value('titleYes'),
//     Button::create('否')->value('titleNo'),
//   ]));
// });

// $botman->hears('defaultText2', function(Botman $bot, $text) {
//   $bot->reply(Question::create('標題確定?')->addButtons([
//     Button::create('是')->value('titleYes'),
//     Button::create('否')->value('titleNo'),
//   ]));
// });

// 接收使用者輸入的標題
$botman->hears('標題 {text}', function(Botman $bot, $text) {
  $user = $bot->getUser();
  $firstname = $user->getFirstName();
  $lastname = $user->getLastName();
  $id = $user->getId();
  $hashId = hash('ripemd160', $id);
  $bot->userStorage()->save([
    'userName' => $lastname . ' ' . $firstname,
    'userId' => $hashId,
    'fbId' => $id,
    'title' => $text
  ]);

  $bot->reply(Question::create('標題確定?')->addButtons([
    Button::create('是')->value('titleYes'),
    Button::create('否')->value('titleNo'),
  ]));
});

$botman->hears('titleYes', function(BotMan $bot) {
  $bot->userStorage()->save([
    'titleFlag' => 1,
  ]);
  $bot->typesAndWaits(0.5);
  certifyReply($bot->userStorage(), $bot);
  // $bot->reply('請輸入想對愛人說的話');
});

$botman->hears('titleNo', function(BotMan $bot) {
  $bot->userStorage()->save([
    'titleFlag' => 0,
    'title' => ''
  ]);
  $bot->typesAndWaits(0.5);
  $bot->reply('請重新輸入標題');
});



// -----Step 2-----
// 接收使用者輸入文字  
$botman->hears('內文 {text}', function(BotMan $bot, $text) {
  $bot->userStorage()->save([
    'text' => $text,
  ]);
  $bot->typesAndWaits(0.5);
  $bot->reply(Question::create('文字確定?')->addButtons([
    Button::create('是')->value('textYes'),
    Button::create('否')->value('textNo'),
  ]));
});

$botman->hears('textYes', function(BotMan $bot) {
  $bot->userStorage()->save([
    'textFlag' => 1
  ]);
  $bot->typesAndWaits(1);
  certifyReply($bot->userStorage(), $bot);
  // $bot->reply('請輸入和愛人的合照');
});

$botman->hears('textNo', function(BotMan $bot) {
  $bot->userStorage()->save([
    'textFlag' => 0,
    'text' => ''
  ]);
  $bot->typesAndWaits(0.5);
  $bot->reply('請重新輸入想對愛人說的話');
});



// -----Step 3-----
// 接收使用者輸入圖片
$botman->receivesImages(function(BotMan $bot, $images) {
  foreach ($images as $image) {
    $url=$image->getUrl();
    $bot->userStorage()->save([
      'image' => $url
    ]);
    $bot->typesAndWaits(1);
    $bot->reply(Question::create('圖片確定?')->addButtons([
      Button::create('是')->value('imageYes'),
      Button::create('否')->value('imageNo'),
    ]));
  }
});

$botman->hears('imageYes', function(BotMan $bot) {
  $imageUrl = $bot->userStorage()->get('image');
  $userId = $bot->userStorage()->get('userId');
  $float = rand(0,10000);
  $bot->userStorage()->save([
    'imageFlag' => 1,
    'imageFloat' => $float
  ]);

  saveImage($imageUrl, 'users_data/', 'userImage_' . $userId . '_' . $float . '.png');
  
  $bot->typesAndWaits(1);
  certifyReply($bot->userStorage(), $bot);
});

$botman->hears('imageNo', function(BotMan $bot) {
  $bot->userStorage()->save([
    'imageFlag' => 'false',
    'image' => ''
  ]);

  $bot->typesAndWaits(0.5);
  $bot->reply('請重新輸入和愛人的合照');
});



// -----Step 4-----
// 全部完成與否
$botman->hears('allYes', function (BotMan $bot) {
  $titleContent = $bot->userStorage()->get('title');
  $textContent = $bot->userStorage()->get('text');
  $fbUserName = $bot->userStorage()->get('userName');
  $userId = $bot->userStorage()->get('userId');
  $fbId = $bot->userStorage()->get('fbId');
  $float = $bot->userStorage()->get('imageFloat');
  
  // 判斷檔案名稱(根據使用者玩了幾次)
  // $usageCount = calcUsageCount($userId);

  imageSynthesis($titleContent, $textContent, 'users_data/userImage_' . $userId . '_' . $float . '.png', $fbUserName, $userId, $fbId, $float, $bot);
});

$botman->hears('allNo', function (BotMan $bot) {
  $bot->userStorage()->delete();

  $bot->typesAndWaits(0.5);
  $bot->reply('重新開始，請輸入標題');
});

// Start listening
$botman->listen();
