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
    'token' => 'EAAgFGCRdh0YBACBiFOo0zoMUiqgRqKxRohHwAgFBNN6hyPpwR9jPZARtOqbZAZAMOYnIW2icryiW4OQgsp5EKGjPd8EkdEg9h0EoiQnOPLV4BzYYSYg10iK1CfSqFsjsHJEOwYgyJvxZCt8ZAtAlwI5AJzPSfSDfib7nO4ZBrnqYQzhyF6uUgUZCGgh5dA3B6sZD',
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
  $textSpace = 50;
  $titleWidth = 11;
  $textWidth = 13;
  $titleCharWidth = 53;
  $titleCharHalfWidth = 27;
  $totalTitleWidth = $titleWidth * $titleCharWidth;

  // 卡片合成
  $editor = Grafika::createEditor();
  $editor->open($image1, 'card_materials/background.png');
  $editor->open($image2, $srcImage);
  $editor->open($image3, 'card_materials/frame.png');
  $editor->open($image4, 'card_materials/cover_bg.png');

  // 圖片resize, resizeExact, resizeFill, resizeFit
  $editor->resizeFill($image2, 435, 480);

  // 圖片旋轉
  $editor->rotate($image2, 8, new Color('#ffffff'));

  // 圖片合成
  $editor->blend($image1, $image2, 'normal', 1, 'top-left', 0, 225);
  $editor->blend($image1, $image3 , 'normal', 1, 'top-left', 0, 225);

  // 計算有幾個全形半形
  function computeChar($srcString) {
    $titleStringLength = mb_strlen($srcString, "utf-8");
    $titleHalfStringLength = strlen($srcString);
    $halfCharNum = ($titleStringLength * 3 - $titleHalfStringLength) * 0.5;
    $fullCharNum = $titleStringLength - $halfCharNum;
    
    return ['half' => $halfCharNum, 'full' => $fullCharNum];
  }

  // 判斷是否需要斷行
  function breakLine($srcString, $limit) {
    $stringLength = mb_strlen($srcString, "utf-8");
    $srcStringArray = [];
    $distArray = [];
    
    for ($i = 0; $i < $stringLength; $i++) {
      array_push($srcStringArray, mb_substr($srcString, $i, 1, "utf-8"));
    }
    
    $tempString = '';
    $lengthCount = 0;
    for ($i = 0; $i < sizeof($srcStringArray); $i++) {
      $tempString .= $srcStringArray[$i];

      if (computeChar($srcStringArray[$i])['full'] == 1) {
        $lengthCount++;
      } else {
        $lengthCount += 0.5;
      }

      if($lengthCount >= $limit || $i == sizeof($srcStringArray) - 1) {
        array_push($distArray, $tempString);
        $tempString = '';
        $lengthCount = 0;
      }
    }

    return $distArray;
  }

  // 標題
  $titleLineCount = 0;
  for ($i = 0; $i <= $titleArrayLength - 1; $i++) {  
    $titleStringArray = breakLine($titleArray[$i], $titleWidth);
    $titleStringArrayLength = sizeof(breakLine($titleArray[$i], $titleWidth));
    for ($j = 0; $j < $titleStringArrayLength; $j++) {
      $halfCharNum = computeChar($titleStringArray[$j])['half'];
      $fullCharNum = computeChar($titleStringArray[$j])['full'];
      $translateX = $totalTitleWidth - ($halfCharNum * $titleCharHalfWidth + $fullCharNum * $titleCharWidth);

      $editor->text($image1, $titleStringArray[$j], 40, 190 + $translateX, 90 + ($titleSpace * $titleLineCount), null, 'fonts/ARMingB5Heavy.otf', 0);
      $titleLineCount++;
    }
  } 

  // 內文
  $textLineCount = 0;
  for ($i = 0; $i <= $textArrayLength - 1; $i++) {
    $textStringArray = breakLine($textArray[$i], $textWidth);
    $textStringArrayLength = sizeof(breakLine($textArray[$i], $textWidth));
    for ($j = 0; $j < $textStringArrayLength; $j++) {
      $editor->text($image1, $textStringArray[$j], 20, 405, 270 + ($textSpace * $textLineCount), null, 'fonts/ARMingB5Medium.otf', 0);
      $textLineCount++;
    }
  }

  $editor->save($image1, 'users_data/cards_dist/mothersCard_' . $distPath);

  // 合成網頁大卡片
  // $editor->resizeFill($image1, 560, 560);
  // $editor->blend($image4, $image1, 'normal', 1, 'center', 0, 0);
  // $editor->save($image4, 'users_data/covers_dist/cover_' . $distPath);

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

  // 抓用戶性別等資料
  // $userDataUrl = 'https://graph.facebook.com/' . $fbId . '?fields=first_name,last_name,profile_pic,gender,locale,timezone&access_token=EAAgFGCRdh0YBACBiFOo0zoMUiqgRqKxRohHwAgFBNN6hyPpwR9jPZARtOqbZAZAMOYnIW2icryiW4OQgsp5EKGjPd8EkdEg9h0EoiQnOPLV4BzYYSYg10iK1CfSqFsjsHJEOwYgyJvxZCt8ZAtAlwI5AJzPSfSDfib7nO4ZBrnqYQzhyF6uUgUZCGgh5dA3B6sZD';

  // $userDataContent = file_get_contents($userDataUrl);
  // $userDataJson = json_decode($userDataContent, true);
  // $userGender = $userDataJson['gender'];
  // $userLocale = $userDataJson['locale'];
  // $userTimezone = $userDataJson['timezone'];

  $apiKey;
  $inputUserName = $fbUserName;
  $inputUserId = $fbId;
  $inputImage = 'https://newmedia.udn.com.tw/mothers_day_bot/users_data/cards_dist/mothersCard_' . $distPath;
  $inputCover = 'https://newmedia.udn.com.tw/mothers_day_bot/users_data/covers_dist/cover_' . $distPath;

  $query = "SELECT MAX(usage_count) AS usage_count FROM cards WHERE user_id=" . $inputUserId;
  $result = mysqli_query($conn, $query);
  
  if (mysqli_num_rows($result) > 0) {
    while($row = $result->fetch_assoc()) {
      $updateCount = $row['usage_count'] + 1;
      $apiKey = $inputUserId . '_' . $updateCount;
      $bot->userStorage()->save([
        'apiKey' => $apiKey,
      ]);
      $sql =  "INSERT INTO cards (api_key, user_name, user_id, image, cover_image, usage_count) VALUES ('" . $apiKey . "','" . $inputUserName . "','" . $inputUserId . "','" . $inputImage . "','" . $inputCover . "'," . $updateCount .")";
      // $sql =  "INSERT INTO cards (api_key, user_name, user_id, image, cover_image, gender, locale, time_zone, usage_count) VALUES ('" . $apiKey ."','" . $inputUserName . "','" . $inputUserId . "','" . $inputImage . "','" . $inputCover . "','" . $userGender . "','" . $userLocale . "','" . $userTimezone . "'," . $updateCount . ")";
      $conn->query($sql);
    }
  } else {
    $apiKey = $inputUserId . '_1';
    $bot->userStorage()->save([
      'apiKey' => $apiKey,
    ]);
    $sql =  "INSERT INTO cards (api_key, user_name, user_id, image, cover_image) VALUES ('" . $apiKey . "','" . $inputUserName . "','" . $inputUserId . "','" . $inputImage . "','" . $inputCover . "')";
    // $sql =  "INSERT INTO cards (api_key, user_name, user_id, image, cover_image, gender, locale, time_zone) VALUES ('" . $apiKey . "','" . $inputUserName . "','" . $inputUserId . "','" . $inputImage . "','" . $inputCover . "','" . $userGender . "','" . $userLocale . "','" . $userTimezone . "')";
    $conn->query($sql);
  }

  // end計次
  $servername = 'localhost';
  $username = 'newmedia';
  $password = 'newmedia';
  $dbname = 'mothers_day_bot';
  $conn = new mysqli($servername, $username, $password, $dbname);
  mysqli_query($conn, "SET NAMES UTF8");
  if($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
  }

  $query = "SELECT count FROM button_clicks WHERE name='end'";
  $result = mysqli_query($conn, $query);
  if (mysqli_num_rows($result) > 0) {
    while($row = $result->fetch_assoc()) {
      $updateCount = $row['count'] + 1;
      $sql = "UPDATE button_clicks SET count=" . $updateCount . " WHERE name='end'";
      $conn->query($sql);
    }
  }

  $conn->close();
  
  $bot->userStorage()->save([
    'finish' => 1,
  ]);

  // 回覆連結 
  $bot->reply(GenericTemplate::create()
    ->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)
    ->addElements([
      Element::create('2019聯合報粉絲頁母親節卡片')
        ->subtitle('')
        ->image('https://newmedia.udn.com.tw/mothers_day_bot/users_data/cards_dist/mothersCard_' . $distPath)
        ->addButton(ElementButton::create('分享卡片')
          ->url("https://udn.com/upf/newmedia/2019_data/lovecard/#" . $apiKey . "")
        )
        ->addButton(ElementButton::create('重新做一張卡片')
          ->payload('重新做卡片')
          ->type('postback')
        )
        ->addButton(ElementButton::create('下載卡片')
          ->payload('downloadImage')
          ->type('postback')
        )
    ])
  );
}

function certifyReply($userStorage, $bot) {
  $titleFlag = $userStorage->get('titleFlag');
  $textFlag = $userStorage->get('textFlag');
  $imageFlag = $userStorage->get('imageFlag');

  if ($titleFlag != 1) {
    $bot->userStorage()->save([
      'enterTitleFlag' => 1,
    ]);

    $bot->reply(GenericTemplate::create()
      ->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)
      ->addElements([
        Element::create('STEP 1 : 請自行輸入標題，或選擇預設標題')
          ->subtitle('')
          ->image('')
          ->addButton(ElementButton::create('點我自行輸入標題')
            ->payload('userInputTitle')
            ->type('postback')
          )
          ->addButton(ElementButton::create('謝謝您無私的愛和包容/母親節快樂！')
            ->payload('defaultTitle1')
            ->type('postback')
          )
      ])
    );
  } else if ($textFlag != 1) {
    $bot->userStorage()->save([
      'enterTextFlag' => 1,
    ]);

    $bot->reply(GenericTemplate::create()
      ->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)
      ->addElements([
        Element::create('STEP 2 : 請自行輸入感謝文，或選擇預設感謝內容')
          ->subtitle('')
          ->image('')
          ->addButton(ElementButton::create('點我自行輸入感謝文')
            ->payload('userInputText')
            ->type('postback')
          )
          ->addButton(ElementButton::create('感謝您無私的付出/在這特別的日子/送給您...')
            ->payload('defaultText1')
            ->type('postback')
          )
      ])
    );
  } else if ($imageFlag != 1) {
    $bot->userStorage()->save([
      'enterImageFlag' => 1,
    ]);

    $bot->reply(GenericTemplate::create()
      ->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)
      ->addElements([
        Element::create('STEP 3 : 可自行挑選一張喜愛的照片，再送出訊息，或使用預設照片')
          ->subtitle('')
          ->image('')
          ->addButton(ElementButton::create('挑選照片')
            ->payload('userInputImage')
            ->type('postback')
          )
          ->addButton(ElementButton::create('預設照片')
            ->payload('defaultImage1')
            ->type('postback')
          )
      ])
    );
  } else {
    $bot->reply('💌卡片製作中，請稍候...');
    $titleContent = $userStorage->get('title');
    $textContent = $userStorage->get('text');
    $fbUserName = $userStorage->get('userName');
    $userId = $userStorage->get('userId');
    $fbId = $userStorage->get('fbId');
    $float = $userStorage->get('imageFloat');
    $defaultImageFlag = $userStorage->get('defaultImageFlag');
        
    if ($defaultImageFlag != 1) {
      imageSynthesis($titleContent, $textContent, 'users_data/userImage_' . $userId . '_' . $float . '.png', $fbUserName, $userId, $fbId, $float, $bot);
    } else {
      imageSynthesis($titleContent, $textContent, 'card_materials/default_image/1.png', $fbUserName, $userId, $fbId, $float, $bot);
    }
  }
}

// -----啟動-----
$botman->hears('我要留言', function(BotMan $bot) {
  $bot->userStorage()->delete();
});

// 處理特別情境輸入
$botman->hears('{text}', function(BotMan $bot, $text) {
  if (
    $text != '開始' &&
    $text != 'defaultText1' &&
    $text != 'userInputText' &&
    $text != 'defaultTitle1' &&
    $text != 'userInputTitle' &&
    $text != 'defaultImage1' &&
    $text != 'userInputImage' &&
    $text != 'downloadImage'
  ) {
    $enterTitleFlag = $bot->userStorage()->get('enterTitleFlag');
    $enterTextFlag = $bot->userStorage()->get('enterTextFlag');
    $enterImageFlag = $bot->userStorage()->get('enterImageFlag');
    $userInputTitleFlag = $bot->userStorage()->get('userInputTitleFlag');
    $userInputTextFlag = $bot->userStorage()->get('userInputTextFlag');
    $userInputImageFlag = $bot->userStorage()->get('userInputImageFlag');

    if ($enterTextFlag == 1) {
      // 如果進入text conversation
      if ($userInputTextFlag == 1) {
        // 如果有按自行輸入按鈕
        $bot->userStorage()->save([
          'enterTextFlag' => 0,
          'userInputTextFlag' => 0,
          'textFlag' => 1,
          'text' => $text
        ]);
        certifyReply($bot->userStorage(), $bot);
      } else if ($text != 'defaultText1' && $text != 'userInputText') {
        // 按鈕以外的任何對話，重新問一次「請輸入標題」
        certifyReply($bot->userStorage(), $bot);
      }
    } else if ($enterTitleFlag == 1) {
      // 如果進入title conversation
      if ($userInputTitleFlag == 1) {
        // 如果有按自行輸入按鈕
        $bot->userStorage()->save([
          'enterTitleFlag' => 0,
          'userInputTitleFlag' => 0,
          'titleFlag' => 1,
          'title' => $text
        ]);
        certifyReply($bot->userStorage(), $bot);
      } else if ($text != 'defaultTitle1' && $text != 'userInputTitle') {
        // 按鈕以外的任何對話
        certifyReply($bot->userStorage(), $bot);
      }
    } else if ($enterImageFlag == 1) {
      // 如果進入image conversation
      if ($userInputImageFlag == 1 && $text != '%%%_IMAGE_%%%') {
        // 如果選擇自行上傳照片
        $bot->reply('❤️請選擇一張合照（建議上傳直式照片）');
      } else if ($text != 'defaultImage1' && $text != 'userInputImage' && $text != '%%%_IMAGE_%%%') {
        certifyReply($bot->userStorage(), $bot);
      }
    }
  }
});

// 進入點
$botman->hears('開始', function(BotMan $bot) {
  $bot->userStorage()->delete();

  // 回覆連結
  $bot->reply(GenericTemplate::create()
    ->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)
    ->addElements([
      Element::create('💌聯合報母親節卡片製作')
        ->subtitle('依步驟進行，即可完成
        1.輸入「標題」
        2.輸入「感謝文」
        3.輸入「合照」
        ')
        ->image('https://newmedia.udn.com.tw/mothers_day_bot/card_materials/example5.png')
        ->addButton(ElementButton::create('開始製作卡片')
        ->payload('開始製作卡片')
        ->type('postback')
      )
    ])
  );

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
});

$botman->hears('重新做卡片', function(BotMan $bot) {
    // restart按鈕計次
    $servername = 'localhost';
    $username = 'newmedia';
    $password = 'newmedia';
    $dbname = 'mothers_day_bot';
    $conn = new mysqli($servername, $username, $password, $dbname);
    mysqli_query($conn, "SET NAMES UTF8");
    if($conn->connect_error) {
      die('Connection failed: ' . $conn->connect_error);
    }

    $query = "SELECT count FROM button_clicks WHERE name='restart'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
      while($row = $result->fetch_assoc()) {
        $updateCount = $row['count'] + 1;
        $sql = "UPDATE button_clicks SET count=" . $updateCount . " WHERE name='restart'";
        $conn->query($sql);
      }
    }
    $conn->close();

  $bot->userStorage()->delete();

  // 回覆連結
  $bot->reply(GenericTemplate::create()
    ->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)
    ->addElements([
      Element::create('💌聯合報母親節卡片製作')
        ->subtitle('依步驟進行，即可完成
        1.輸入「標題」
        2.輸入「感謝文」
        3.輸入「合照」
        ')
        ->image('https://newmedia.udn.com.tw/mothers_day_bot/card_materials/example5.png')
        ->addButton(ElementButton::create('開始製作卡片')
        ->payload('開始製作卡片')
        ->type('postback')
      )
    ])
  );

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
});

$botman->on('messaging_referrals', function($payload, BotMan $bot) {
  $ref = $payload['referral']['ref'];
  if ($ref == 'GET_STARTED') {
    $bot->userStorage()->delete();

    // 回覆連結
    $bot->reply(GenericTemplate::create()
      ->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)
      ->addElements([
        Element::create('💌聯合報母親節卡片製作')
          ->subtitle('依步驟進行，即可完成
          1.輸入「標題」
          2.輸入「感謝文」
          3.輸入「合照」')
          ->image('https://newmedia.udn.com.tw/mothers_day_bot/card_materials/example5.png')
          ->addButton(ElementButton::create('開始製作卡片')
          ->payload('開始製作卡片')
          ->type('postback')
        )
      ])
    );
   
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
  }
});

$botman->hears('開始製作卡片', function(BotMan $bot) {
  // start按鈕計次
  $servername = 'localhost';
  $username = 'newmedia';
  $password = 'newmedia';
  $dbname = 'mothers_day_bot';
  $conn = new mysqli($servername, $username, $password, $dbname);
  mysqli_query($conn, "SET NAMES UTF8");
  if($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
  }

  $query = "SELECT count FROM button_clicks WHERE name='enter'";
  $result = mysqli_query($conn, $query);
  if (mysqli_num_rows($result) > 0) {
    while($row = $result->fetch_assoc()) {
      $updateCount = $row['count'] + 1;
      $sql = "UPDATE button_clicks SET count=" . $updateCount . " WHERE name='enter'";
      $conn->query($sql);
    }
  }
  $conn->close();
  certifyReply($bot->userStorage(), $bot);
});

// -----Step 1-----
// 使用預設標題1
$botman->hears('defaultTitle1', function(BotMan $bot) {
  $titleFlag = $bot->userStorage()->get('titleFlag');
  if ($titleFlag != 1) {
    $bot->userStorage()->save([
      'enterTitleFlag' => 0,
      'titleFlag' => 1,
      'title' => '謝謝您無私的愛和包容/母親節快樂！'
    ]);
    $bot->typesAndWaits(0.5);
    certifyReply($bot->userStorage(), $bot);
  } else {
    certifyReply($bot->userStorage(), $bot);
  }
});

// 使用者輸入標題
$botman->hears('userInputTitle', function(BotMan $bot) {
  $titleFlag = $bot->userStorage()->get('titleFlag');
  if ($titleFlag != 1) {
    $bot->userStorage()->save([
      'userInputTitleFlag' => 1,
    ]);
  
    $bot->reply('一行請勿超過11個中文字（含標點符號），最多22個中文字，不超過兩行。（如須換行，請輸入"/"）');
  } else {
    certifyReply($bot->userStorage(), $bot);
  }
});



// -----Step 2-----
// 使用預設內文1
$botman->hears('defaultText1', function(BotMan $bot) {
  $textFlag = $bot->userStorage()->get('textFlag');
  if ($textFlag != 1) {
    $bot->userStorage()->save([
      'enterTextFlag' => 0,
      'textFlag' => 1,
      'text' => '感謝您無怨無悔的付出/在這特別的日子/送給您這張特製的小卡片/祝您母親節快樂'
    ]);
    $bot->typesAndWaits(0.5);
    certifyReply($bot->userStorage(), $bot);
  } else {
    certifyReply($bot->userStorage(), $bot);
  }
});

// 使用者輸入內文
$botman->hears('userInputText', function(BotMan $bot) {
  $textFlag = $bot->userStorage()->get('textFlag');
  if ($textFlag != 1) {
    $bot->userStorage()->save([
      'userInputTextFlag' => 1,
    ]);
  
    $bot->reply('一行請勿超過13個中文字（含標點符號），最多52個中文字，不超過四行。（如須換行，請輸入"/"）');
  } else {
    certifyReply($bot->userStorage(), $bot);
  }
});



// -----Step 3-----
// 使用預設圖片1
$botman->hears('defaultImage1', function(BotMan $bot) {
  $imageFlag = $bot->userStorage()->get('imageFlag');
  if ($imageFlag != 1) {
    $float = rand(0,10000);
    $bot->userStorage()->save([
      'enterImageFlag' => 0,
      'defaultImageFlag' => '1',
      'imageFlag' => 1,
      'imageFloat' => $float
    ]);
    $bot->typesAndWaits(0.5);
    certifyReply($bot->userStorage(), $bot);
  } else {
    certifyReply($bot->userStorage(), $bot);
  }
});

$botman->hears('userInputImage', function(BotMan $bot) {
  $imageFlag = $bot->userStorage()->get('imageFlag');
  if ($imageFlag != 1) {
    $bot->userStorage()->save([
      'userInputImageFlag' => 1,
    ]);
  
    $bot->reply('❤️請自行從手機或桌機相本內，挑選一張喜愛的照片，或手機即時拍照，再送出訊息（建議使用直式照片）');
  } else {
    certifyReply($bot->userStorage(), $bot);
  }
});

// 接收使用者輸入圖片
$botman->receivesImages(function(BotMan $bot, $images) {
  $enterImageFlag = $bot->userStorage()->get('enterImageFlag');
  if ($enterImageFlag == 1 && $bot->userStorage()->get('imageFlag') != 1) {
    foreach ($images as $image) {
      $bot->reply('🖼圖片上傳中，請稍候...');
      $imageUrl=$image->getUrl();
      $userId = $bot->userStorage()->get('userId');
      $float = rand(0,10000);
      $bot->userStorage()->save([
        'enterImageFlag' => 0,
        'userInputImageFlag' => 0,
        'imageFlag' => 1,
        'imageFloat' => $float
      ]);
      saveImage($imageUrl, 'users_data/', 'userImage_' . $userId . '_' . $float . '.png');
      
      $bot->typesAndWaits(1);
      certifyReply($bot->userStorage(), $bot);
    }
  }
});

$botman->hears('downloadImage', function(BotMan $bot) {
  $finish = $bot->userStorage()->get('finish');
  if ($finish == 1) {

    // download按鈕計次
    $servername = 'localhost';
    $username = 'newmedia';
    $password = 'newmedia';
    $dbname = 'mothers_day_bot';
    $conn = new mysqli($servername, $username, $password, $dbname);
    mysqli_query($conn, "SET NAMES UTF8");
    if($conn->connect_error) {
      die('Connection failed: ' . $conn->connect_error);
    }

    $query = "SELECT count FROM button_clicks WHERE name='download'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
      while($row = $result->fetch_assoc()) {
        $updateCount = $row['count'] + 1;
        $sql = "UPDATE button_clicks SET count=" . $updateCount . " WHERE name='download'";
        $conn->query($sql);
      }
    }
    $conn->close();

    // 傳送卡片
    $userId = $bot->userStorage()->get('userId');
    $float = $bot->userStorage()->get('imageFloat');
  
    $bot->reply('💌卡片傳送中...');
    $attachment = new Image('https://newmedia.udn.com.tw/mothers_day_bot/users_data/cards_dist/mothersCard_' . $userId . '_' . $float . '.png');
    $message = OutgoingMessage::create('')->withAttachment($attachment);
    $bot->reply($message);
  } else {
    // fail_download按鈕計次
    $servername = 'localhost';
    $username = 'newmedia';
    $password = 'newmedia';
    $dbname = 'mothers_day_bot';
    $conn = new mysqli($servername, $username, $password, $dbname);
    mysqli_query($conn, "SET NAMES UTF8");
    if($conn->connect_error) {
      die('Connection failed: ' . $conn->connect_error);
    }

    $query = "SELECT count FROM button_clicks WHERE name='fail_download'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
      while($row = $result->fetch_assoc()) {
        $updateCount = $row['count'] + 1;
        $sql = "UPDATE button_clicks SET count=" . $updateCount . " WHERE name='fail_download'";
        $conn->query($sql);
      }
    }
    $conn->close();

    $bot->reply('連結已過期，請點上方「分享卡片」按鈕');
  }
});



// Start listening
$botman->listen();
