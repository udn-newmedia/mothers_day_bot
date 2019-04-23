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
    'token' => 'EAAgFGCRdh0YBABX9wV2YQSgjGDXeVq5ghn4BcT3OOfkDqejgAiXOJZBtXASTMWmQTRrdKqFOV7ZC54brAWemZBh4RL1yit7BVUs6jmD4oQzAnDipGSrugZAOidC3yKlDPa7KJPdTGCjG9ZArfFxafHsvxs9PwyEXVd4omj4FgRwZDZD',
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
  // Card parameter
  $distPath = $userId . '_' . $float . '.png';
  $titleArray = explode("/", $srcTitle);
  $textArray = explode("/", $srcText);
  $titleArrayLength = sizeof($titleArray);
  $textArrayLength = sizeof($textArray);
  $titleSpace = 70;
  $textSpace = 40;

  // 卡片合成
  $editor = Grafika::createEditor();
  $editor->open($image1, 'card_materials/background.png');
  $editor->open($image2, $srcImage);
  $editor->open($image3, 'card_materials/frame.png');
  $editor->open($image4, 'card_materials/cover_bg.png');

  // 圖片resize, resizeExact, resizeFill, resizeFit
  $editor->resizeFill($image2, 460, 360);
  
  // 圖片旋轉
  $editor->rotate($image2, 9, new Color('#ffffff'));
  
  // 圖片合成
  $editor->blend($image1, $image2, 'normal', 1, 'top-left', 25, 212);
  $editor->blend($image1, $image3 , 'normal', 1, 'top-left', 0, 212);
  
  // 標題
  for ($i = 0; $i <= $titleArrayLength - 1; $i++) {
    $editor->text($image1, $titleArray[$i], 44, 250, 90 + ($titleSpace * $i), null, 'fonts/ARMingB5Heavy.otf', 0);
  } 
  
  // 內文
  for ($i = 0; $i <= $textArrayLength - 1; $i++) {
    $editor->text($image1, $textArray[$i], 24, 45, 600 + ($textSpace * $i), null, 'fonts/ARMingB5Medium.otf', 0);
  }

  $editor->save($image1, 'users_data/cards_dist/mothersCard_' . $distPath);

  // 合成網頁大卡片
  $editor->resizeFill($image1, 560, 560);
  $editor->blend($image4, $image1, 'normal', 1, 'center', 0, 0);
  $editor->save($image4, 'users_data/covers_dist/cover_' . $distPath);

  // 寫進資料庫
  // Database config
  $servername = 'localhost';
  $username = 'newmedia';
  $password = 'newmedia';
  $dbname = 'mothers_day_bot';
  $conn = new mysqli($servername, $username, $password, $dbname);
  mysqli_query($conn, "SET NAMES UTF8");

  if($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
  }

  $inputUserName = $fbUserName;
  $inputUserId = $fbId;
  $inputImage = 'https://nmdap.udn.com.tw/newmedia/mothers_day_bot/users_data/cards_dist/mothersCard_' . $distPath;
  $inputCover = 'https://nmdap.udn.com.tw/newmedia/mothers_day_bot/users_data/covers_dist/cover_' . $distPath;

  $query = "SELECT usage_count FROM cards WHERE user_id=" . $inputUserId;
  $result = mysqli_query($conn, $query);

  if (mysqli_num_rows($result) > 0) {
    while($row = $result->fetch_assoc()) {
      $updateCount = $row['usage_count'] + 1;

      $sql = "UPDATE cards SET usage_count=" . $updateCount . " WHERE user_id=" . $inputUserId;
      $conn->query($sql);

      $sql2 = "UPDATE cards SET image='" . $inputImage . "' WHERE user_id=" . $inputUserId;
      $conn->query($sql2);
      
      $sql3 = "UPDATE cards SET cover_image='" . $inputCover . "' WHERE user_id=" . $inputUserId;
      $conn->query($sql3);
    }
  } else {
    $sql = "INSERT INTO cards (user_name, user_id, image, cover_image) VALUES ('" . $inputUserName . "', '" . $inputUserId . "', '" . $inputImage . "','" . $inputCover . "')";

    $conn->query($sql);
  }

  // 從資料庫抓主鍵ID
  // $queryId = "SELECT id FROM cards WHERE user_id=" . $inputUserId;
  // $resultId = mysqli_query($conn, $queryId);
  // $row = mysqli_fetch_array($resultId);
  // $primaryKey = $row['id'];

  $conn->close();

  // 回覆連結
  $bot->reply(GenericTemplate::create()
    ->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)
    ->addElements([
      Element::create('印刷廠印製完成...')
        ->subtitle('前往卡片網頁')
        ->image('https://nmdap.udn.com.tw/newmedia/mothers_day_bot/users_data/cards_dist/mothersCard_' . $distPath)
        ->addButton(ElementButton::create('visit')
          ->url('https://nmdap.udn.com.tw/newmedia/mothers_day_bot/#' . $inputUserId)
        )
    ])
  );
}

function certifyReply($userStorage, $bot) {
  $titleFlag = $userStorage->get('titleFlag');
  $textFlag = $userStorage->get('textFlag');
  $imageFlag = $userStorage->get('imageFlag');

  if ($titleFlag != 1) {
    // $bot->reply('請輸入標題');
    $bot->reply(Question::create('請輸入標題')->addButtons([
      Button::create('自行輸入標題')->value('userInputTitle'),
      Button::create('預設標題1: [預設標題1]')->value('defaultTitle1'),
      Button::create('預設標題2: [預設標題2]')->value('defaultTitle2')
    ]));
  } else if ($textFlag != 1) {
    // $bot->reply('請輸入內文');
    $bot->reply(Question::create('請輸入內文')->addButtons([
      Button::create('自行輸入內文')->value('userInputText'),
      Button::create('預設內文1: [預設內文1]')->value('defaultText1'),
      Button::create('預設內文2: [預設內文2]')->value('defaultText2')
    ]));
  } else if ($imageFlag != 1) {
    $bot->reply(Question::create('請選擇一張合照，如果不上傳合照，機器人使用預設圖片。')->addButtons([
      Button::create('我要上傳')->value('userInputImage'),
      Button::create('我不上傳')->value('defaultImage1'),
    ]));
    // $bot->reply('上為預設圖片1，下為預設圖片2');
    // $image = Image::url('https://nmdap.udn.com.tw/newmedia/mothers_day_bot/card_materials/default_image/1.png');
    // $message = OutgoingMessage::create('')->withAttachment($image);
    // $bot->reply($message);
    // $bot->reply(Question::create('請選擇圖片')->addButtons([
    //   Button::create('輸入自己的圖片')->value('userInputImage'),
    //   Button::create('預設圖片1')->value('defaultImage1'),
    //   Button::create('預設圖片2')->value('defaultImage2')
    // ]));
  } else {
    $bot->reply(Question::create('是→開始印卡片 | 否→重新開始')->addButtons([
      Button::create('是')->value('allYes'),
      Button::create('否')->value('我要做卡片'),
    ]));
  }
}

// -----啟動-----
$botman->hears('我要做卡片', function(BotMan $bot) {
  $bot->reply('本活動要依序上傳「標題」、「內文」、「合照」，就可完成小卡片的製作（如示意圖）');
  $bot->userStorage()->delete();
  $user = $bot->getUser();
  $firstname = $user->getFirstName();
  $lastname = $user->getLastName();
  $id = $user->getId();
  $hashId = hash('ripemd160', $id);
  $bot->userStorage()->save([
    'userName' => $lastname . ' ' . $firstname,
    'userId' => $hashId,
    'fbId' => $id,
  ]);

  certifyReply($bot->userStorage(), $bot);
});



// -----Step 1-----
// 使用預設標題1
$botman->hears('defaultTitle1', function(BotMan $bot) {
  $bot->userStorage()->save([
    'title' => '[預設標題1]/[預設標題1]'
  ]);

  $bot->reply(Question::create('標題確定?')->addButtons([
    Button::create('輸入完成')->value('titleYes'),
    Button::create('我想重寫')->value('我要做卡片'),
  ]));
});

// 使用預設標題2
$botman->hears('defaultTitle2', function(BotMan $bot) {
  $bot->userStorage()->save([
    'title' => '[預設標題2]/[預設標題2]'
  ]);

  $bot->reply(Question::create('標題確定?')->addButtons([
    Button::create('輸入完成')->value('titleYes'),
    Button::create('我想重寫')->value('我要做卡片'),
  ]));
});

// 使用者輸入標題
$botman->hears('userInputTitle', function(BotMan $bot) {
  $bot->reply('請輸入標題，開頭請先輸入「標題」，再輸入你的標題，例如：標題媽媽母親節快樂("標題"2字不會出現在卡片裡面)');
});

// 接收使用者輸入的標題
$botman->hears('標題{text}', function(BotMan $bot, $text) {
  $bot->userStorage()->save([
    'title' => $text
  ]);
  $bot->reply(Question::create('標題確定?')->addButtons([
    Button::create('輸入完成')->value('titleYes'),
    Button::create('我想重寫')->value('我要做卡片'),
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



// -----Step 2-----
// 使用預設內文1
$botman->hears('defaultText1', function(BotMan $bot) {
  $bot->userStorage()->save([
    'text' => '[預設內文1]/[預設內文1]/[預設內文1]/[預設內文1]'
  ]);

  $bot->reply(Question::create('內文確定?')->addButtons([
    Button::create('輸入完成')->value('textYes'),
    Button::create('我想重寫')->value('我要做卡片'),
  ]));
});

// 使用預設內文2
$botman->hears('defaultText2', function(BotMan $bot) {
  $bot->userStorage()->save([
    'text' => '[預設內文2]/[預設內文2]'
  ]);

  $bot->reply(Question::create('內文確定?')->addButtons([
    Button::create('輸入完成')->value('textYes'),
    Button::create('我想重寫')->value('我要做卡片'),
  ]));
});

// 使用者輸入內文
$botman->hears('userInputText', function(BotMan $bot) {
  $bot->reply('請輸入內文，也就是你想對媽媽說的話，開頭請先輸入「內文」，再輸入你想說的話，例如：內文親愛的媽媽/在這特別的日子裡...("內文"2字不會出現在卡片裡面)\n\n請勿輸入超過52字（含標點符號），如須換行，請輸入"/"');
});

// 接收使用者輸入文字  
$botman->hears('內文{text}', function(BotMan $bot, $text) {
  $bot->userStorage()->save([
    'text' => $text,
  ]);
  $bot->typesAndWaits(0.5);
  $bot->reply(Question::create('文字確定?')->addButtons([
    Button::create('輸入完成')->value('textYes'),
    Button::create('我想重寫')->value('我要做卡片'),
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



// -----Step 3-----
// 使用預設圖片1
$botman->hears('defaultImage1', function(BotMan $bot) {
  $bot->userStorage()->save([
    'image' => 'https://nmdap.udn.com.tw/newmedia/mothers_day_bot/card_materials/default_image/1.png'
  ]);
  $bot->typesAndWaits(0.5);
  $bot->reply(Question::create('圖片確定?')->addButtons([
    Button::create('上傳確定')->value('imageYes'),
    Button::create('我想重選')->value('我要做卡片'),
  ]));
});

// 使用預設圖片2
$botman->hears('defaultImage2', function(BotMan $bot) {
  $bot->userStorage()->save([
    'image' => 'https://nmdap.udn.com.tw/newmedia/mothers_day_bot/card_materials/default_image/2.png'
  ]);
  $bot->typesAndWaits(0.5);
  $bot->reply(Question::create('圖片確定?')->addButtons([
    Button::create('上傳確定')->value('imageYes'),
    Button::create('我想重選')->value('我要做卡片'),
  ]));
});

$botman->hears('userInputImage', function(BotMan $bot) {
  $bot->reply('請選擇一張合照（建議上傳直式照片）');
});

// 接收使用者輸入圖片
$botman->receivesImages(function(BotMan $bot, $images) {
  foreach ($images as $image) {
    $url=$image->getUrl();
    $bot->userStorage()->save([
      'image' => $url
    ]);
    $bot->typesAndWaits(0.5);
    $bot->reply(Question::create('圖片確定?')->addButtons([
      Button::create('上傳確定')->value('imageYes'),
      Button::create('我想重選')->value('我要做卡片'),
    ]));
  }
});

$botman->hears('imageYes', function(BotMan $bot) {
  $bot->reply('圖片上傳中，請稍候...');
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



// -----Step 4-----
// 全部完成與否
$botman->hears('allYes', function (BotMan $bot) {
  $bot->reply('卡片製作中，請稍候...');
  $titleContent = $bot->userStorage()->get('title');
  $textContent = $bot->userStorage()->get('text');
  $fbUserName = $bot->userStorage()->get('userName');
  $userId = $bot->userStorage()->get('userId');
  $fbId = $bot->userStorage()->get('fbId');
  $float = $bot->userStorage()->get('imageFloat');
  
  $bot->userStorage()->delete();
  imageSynthesis($titleContent, $textContent, 'users_data/userImage_' . $userId . '_' . $float . '.png', $fbUserName, $userId, $fbId, $float, $bot);
});



// Start listening
$botman->listen();
