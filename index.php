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
    'token' => 'EAAgFGCRdh0YBAFVC0zwxhzWSOY4rJXugLdV3FM8vOvwe34dLSCEoSVCcxKticr23MlrvvOl49tOFH83vJbnZB4avsJwSa4P8PCjKuJJJiVZBejEjx6YrS2TEZAIGpFkxxt6LFdnZAYDgA3n6353exXW5gCdhRNi3aiUC6QOEQQZDZD',
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


  // 抓用戶性別等資料
  $userDataUrl = 'https://graph.facebook.com/' . $fbId . '?fields=first_name,last_name,profile_pic,gender,locale,timezone&access_token=EAAgFGCRdh0YBAFVC0zwxhzWSOY4rJXugLdV3FM8vOvwe34dLSCEoSVCcxKticr23MlrvvOl49tOFH83vJbnZB4avsJwSa4P8PCjKuJJJiVZBejEjx6YrS2TEZAIGpFkxxt6LFdnZAYDgA3n6353exXW5gCdhRNi3aiUC6QOEQQZDZD';
  $userDataContent = file_get_contents($userDataUrl);
  $userDataJson = json_decode($userDataContent, true);
  $userGender = $userDataJson['gender'];
  $userLocale = $userDataJson['locale'];
  $userTimezone = $userDataJson['timezone'];

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
      $sql4 = "UPDATE cards SET updated_at=NOW() WHERE user_id=" . $inputUserId;
      $conn->query($sql4);
    }
  } else {
    $sql =  "INSERT INTO cards (user_name, user_id, image, cover_image, gender, locale, time_zone) VALUES ('" . $inputUserName . "', '" . $inputUserId . "', '" . $inputImage . "','" . $inputCover . "','" . $userGender . "','" . $userLocale . "','" . $userTimezone . "')";

    $conn->query($sql);
  }

  $conn->close();




  // 回覆連結 
  $url = 'https://graph.facebook.com/v2.6/me/messages?access_token=EAAgFGCRdh0YBAFVC0zwxhzWSOY4rJXugLdV3FM8vOvwe34dLSCEoSVCcxKticr23MlrvvOl49tOFH83vJbnZB4avsJwSa4P8PCjKuJJJiVZBejEjx6YrS2TEZAIGpFkxxt6LFdnZAYDgA3n6353exXW5gCdhRNi3aiUC6QOEQQZDZD';

  $ch = curl_init($url);
  $jsonData = 
  '{
    "recipient":{
      "id":"' . $inputUserId . '"
    },
    "message":{
      "attachment": {
        "type": "template",
        "payload": {
          "template_type": "generic",
          "image_aspect_ratio":"square",
          "elements":
          [
            {
              "title": "2019聯合報粉絲頁母親節卡片",
              "image_url": "https://nmdap.udn.com.tw/newmedia/mothers_day_bot/users_data/cards_dist/mothersCard_' . $distPath .'",
              "subtitle": "",
              "default_action": {
                "type": "web_url",
                "url": "https://nmdap.udn.com.tw/newmedia/mothers_day_bot/users_data/cards_dist/mothersCard_' . $distPath .'",
                "messenger_extensions": false,
                "webview_height_ratio": "tall",
              },
              "buttons": [
                {
                  "type": "web_url",
                  "url": "http://nmdap.udn.com.tw/upf/newmedia/2019_data/lovecard/#' . $inputUserId . '",
                  "title": "觀看母親節卡片"
                },
                {
                  "type": "postback",
                  "title": "重新做一張卡片",
                  "payload": "我要做卡片"
                },
                {
                  "type": "element_share",
                  "share_contents": { 
                    "attachment": {
                      "type": "template",
                      "payload": {
                        "template_type": "generic",
                        "image_aspect_ratio":"square",
                        "elements": [
                          {
                            "title": "2019聯合報粉絲頁母親節卡片",
                            "subtitle": "今年的母親節，你打算送媽媽、阿嬤或最照顧你的人什麼東西呢？不如傳給她一張有你們合照的小卡片吧！聯合報粉絲專頁的這個小活動，只要依步驟上傳一張你們的合照、寫上你想對她說的話，我們就可以幫你做好一張小卡，讓妳送給她喔！",
                            "image_url": "https://nmdap.udn.com.tw/newmedia/mothers_day_bot/users_data/cards_dist/mothersCard_' . $distPath . '",
                            "default_action": {
                              "type": "web_url",
                              "url": "http://nmdap.udn.com.tw/upf/newmedia/2019_data/lovecard/#' . $inputUserId . '"
                            },
                            "buttons": [
                              {
                                "type": "web_url",
                                "url": "http://m.me/321100408573986", 
                                "title": "我要做卡片"
                              }
                            ]
                          }
                        ]
                      }
                    }
                  }
                }
              ]
            }
          ]
        }
      }
    }
  }';

  /* curl setting to send a json post data */
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
  $result = curl_exec($ch); // user will get the message

  // $bot->reply(GenericTemplate::create()
  //   ->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)
  //   ->addElements([
  //     Element::create('印刷廠印製完成...')
  //       ->subtitle('')
  //       ->image('https://nmdap.udn.com.tw/newmedia/mothers_day_bot/users_data/cards_dist/mothersCard_' . $distPath)
  //       ->addButton(ElementButton::create('分享卡片')
  //         ->url('http://nmdap.udn.com.tw/upf/newmedia/2019_data/lovecard/#' . $inputUserId)
  //       )
  //       ->addButton(ElementButton::create('重新製作卡片')
  //         ->payload('我要做卡片')
  //         ->type('postback')
  //       ),
  //   ])
  // );
}

function certifyReply($userStorage, $bot) {
  $titleFlag = $userStorage->get('titleFlag');
  $textFlag = $userStorage->get('textFlag');
  $imageFlag = $userStorage->get('imageFlag');

  if ($titleFlag != 1) {
    $bot->userStorage()->save([
      'enterTitleFlag' => 1,
    ]);
    $bot->reply(Question::create('STEP 1 : 請自行輸入標題，或選擇預設標題')->addButtons([
      Button::create('點我自行輸入標題')->value('userInputTitle'),
      Button::create('謝謝您無私的愛和包容/母親節快樂！')->value('defaultTitle1')
      // Button::create('預設標題2: [預設標題2]')->value('defaultTitle2')
    ]));
  } else if ($textFlag != 1) {
    $bot->userStorage()->save([
      'enterTextFlag' => 1,
    ]);
    $bot->reply(Question::create('STEP 2 : 請自行輸入感謝文，或選擇預設感謝內容')->addButtons([
      Button::create('點我自行輸入感謝文')->value('userInputText'),
      Button::create('感謝您無私的付出/在這特別的日子/送給您...')->value('defaultText1')
      // Button::create('預設內文2: [預設內文2]')->value('defaultText2')
    ]));
  } else if ($imageFlag != 1) {
    $bot->userStorage()->save([
      'enterImageFlag' => 1,
    ]);
    $bot->reply(Question::create('STEP 3 : 點我要上傳，再選擇一張合照上傳，如果不上傳合照，可選擇使用預設圖片')->addButtons([
      Button::create('我要上傳')->value('userInputImage'),
      Button::create('預設圖片')->value('defaultImage1'),
    ]));
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

// $input = json_decode(file_get_contents('php://input'), true);
// if (isset($input['entry'][0]['messaging'][0]['sender']['id'])) {
//   $sender = $input['entry'][0]['messaging'][0]['sender']['id']; //sender facebook id
//   $message = $input['entry'][0]['messaging'][0]['message']['text']; //text that user sent

//   $handle = fopen("comment_info.txt", "w");
//   fwrite($handle, $message);
//   fclose($handle);

//   $textArray = explode("\n", $message);
//   $url = 'https://graph.facebook.com/v2.6/me/messages?access_token=EAAgFGCRdh0YBAFVC0zwxhzWSOY4rJXugLdV3FM8vOvwe34dLSCEoSVCcxKticr23MlrvvOl49tOFH83vJbnZB4avsJwSa4P8PCjKuJJJiVZBejEjx6YrS2TEZAIGpFkxxt6LFdnZAYDgA3n6353exXW5gCdhRNi3aiUC6QOEQQZDZD';

//   /*initialize curl*/
//   $ch = curl_init($url);
//   /*prepare response*/
//   $jsonData = '{
//   "recipient":{
//     "id":"' . $sender . '"
//     },
//     "message":{
//       "text":"You said, ' . $textArray[1] . '"
//     }
//   }';
//   /* curl setting to send a json post data */
//   curl_setopt($ch, CURLOPT_POST, 1);
//   curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
//   curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
//   if (!empty($message)) {
//     $result = curl_exec($ch); // user will get the message
//   }
// }

// -----啟動-----
$botman->hears('我要留言', function(BotMan $bot) {
  $bot->userStorage()->delete();
});

$botman->hears('{text}', function(BotMan $bot, $text) {
  if ($text != '我要做卡片') {
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

$botman->hears('我要做卡片', function(BotMan $bot) {
  $bot->userStorage()->delete();

  // 回覆連結
  $bot->reply(GenericTemplate::create()
    ->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)
    ->addElements([
      Element::create('💌聯合報母親節卡片製作')
        ->subtitle('本活動要依序上傳
        1.「標題」
        2.「感謝文」
        3.「合照」
        就可完成小卡片的製作。')
        ->image('https://nmdap.udn.com.tw/newmedia/mothers_day_bot/card_materials/example4.png')
    ])
  );

  // $bot->reply('❤️本活動要依序上傳
  // 1.「標題」
  // 2.「內文」
  // 3.「合照」
  // 就可完成小卡片的製作。
  // （如下示意圖）');
  // $bot->typesAndWaits(0.5);
  // $attachment = new Image('https://nmdap.udn.com.tw/newmedia/mothers_day_bot/card_materials/example.png');
  // $message = OutgoingMessage::create('')->withAttachment($attachment);
  // $bot->reply($message);

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

$botman->on('messaging_referrals', function($payload, BotMan $bot) {
  $ref = $payload['referral']['ref'];
  if ($ref == 'GET_STARTED') {
    $bot->userStorage()->delete();

    // 回覆連結
    $bot->reply(GenericTemplate::create()
      ->addImageAspectRatio(GenericTemplate::RATIO_SQUARE)
      ->addElements([
        Element::create('💌聯合報母親節卡片製作')
          ->subtitle('本活動要依序上傳
          1.「標題」
          2.「內文」
          3.「合照」
          就可完成小卡片的製作。')
          ->image('https://nmdap.udn.com.tw/newmedia/mothers_day_bot/card_materials/example4.png')
      ])
    );
  
    // $bot->reply('❤️本活動要依序上傳
    // 1.「標題」
    // 2.「內文」
    // 3.「合照」
    // 就可完成小卡片的製作。
    // （如下示意圖）');
    // $bot->typesAndWaits(0.5);
    // $attachment = new Image('https://nmdap.udn.com.tw/newmedia/mothers_day_bot/card_materials/example.png');
    // $message = OutgoingMessage::create('')->withAttachment($attachment);
    // $bot->reply($message);
  
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
  }
});

// -----Step 1-----
// 使用預設標題1
$botman->hears('defaultTitle1', function(BotMan $bot) {
  $bot->userStorage()->save([
    'enterTitleFlag' => 0,
    'titleFlag' => 1,
    'title' => '謝謝您無私的愛和包容/母親節快樂！'
  ]);
  $bot->typesAndWaits(0.5);
  certifyReply($bot->userStorage(), $bot);
});

// 使用者輸入標題
$botman->hears('userInputTitle', function(BotMan $bot) {
  $bot->userStorage()->save([
    'userInputTitleFlag' => 1,
  ]);

  $bot->reply('一行請勿超過11個中文字（含標點符號），最多22個中文字，不超過兩行。（如須換行，請輸入"/"）');
});



// -----Step 2-----
// 使用預設內文1
$botman->hears('defaultText1', function(BotMan $bot) {
  $bot->userStorage()->save([
    'enterTextFlag' => 0,
    'textFlag' => 1,
    'text' => '感謝您無怨無悔的付出/在這特別的日子/送給您這張特製的小卡片/祝您母親節快樂'
  ]);
  $bot->typesAndWaits(0.5);
  certifyReply($bot->userStorage(), $bot);
});

// 使用者輸入內文
$botman->hears('userInputText', function(BotMan $bot) {
  $bot->userStorage()->save([
    'userInputTextFlag' => 1,
  ]);

  $bot->reply('一行請勿超過13個中文字（含標點符號），最多52個中文字，不超過四行。（如須換行，請輸入"/"）');
});



// -----Step 3-----
// 使用預設圖片1
$botman->hears('defaultImage1', function(BotMan $bot) {
  $float = rand(0,10000);
  $bot->userStorage()->save([
    'enterImageFlag' => 0,
    'defaultImageFlag' => '1',
    'imageFlag' => 1,
    'imageFloat' => $float
  ]);
  $bot->typesAndWaits(0.5);
  certifyReply($bot->userStorage(), $bot);
});

$botman->hears('userInputImage', function(BotMan $bot) {
  $bot->userStorage()->save([
    'userInputImageFlag' => 1,
  ]);

  $bot->reply('❤️請選擇一張合照（建議上傳直式照片）');
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



// Start listening
$botman->listen();
