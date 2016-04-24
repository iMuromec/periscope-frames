<?php
  if (isset($_POST['periscope'])) {
    //Скачиваем JSON трансляции:
    echo file_get_contents($_POST['periscope']);
    die();
  }
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>I'm watching you!</title>
  <!-- player skins -->
  <link rel="stylesheet" href="//releases.flowplayer.org/6.0.3/skin/functional.css">
  <!-- <link rel="stylesheet" href="//releases.flowplayer.org/6.0.3/skin/playful.css"> -->
  <!-- <link rel="stylesheet" href="//releases.flowplayer.org/6.0.3/skin/minimalist.css"> -->
  
  <!-- for video tag based installs flowplayer depends on jQuery 1.7.2+ -->
  <script src="https://code.jquery.com/jquery-1.11.2.min.js"></script>
  <!-- include flowplayer -->
  <script src="//releases.flowplayer.org/6.0.3/flowplayer.min.js"></script>
  <style>
    html {
      height: 100%;
    }
    body {
      margin: 0;
      padding: 0;
      background: black;
      background: -webkit-linear-gradient(top, black 0%, #2f3a3d 100%);
      background: linear-gradient(to bottom, black 0%, #2f3a3d 100%);
      height: 100%;
      min-height: 100%;
      overflow: hidden;
    }
    .flowplayer {
      width: 20%;
    }
    .flowplayer .fp-time, .flowplayer .fp-title, 
    .flowplayer.is-error .fp-fullscreen { 
      display: none!important;
    }
    .flowplayer.is-error {
      border:0;
      background-color: transparent!important;
    }
    .flowplayer.is-error h2, h2.is-error {
      font-weight: normal;
      color: #a4b8be;
      font-family: sans-serif;
    }
  </style>
</head>
<body>
<?php 
  //Скачиваем страницу с хэштегами periscope'а
  $url = "https://twitter.com/hashtag/periscope?f=tweets&vertical=default&src=hash";
  $input = @file_get_contents($url) or die("<h2 class=\"is-error\">Не удалось загрузить страницу:<br>$url</h2></body></html>");
  
  //Ищем подходящие ссылки (в данном случае это еще и токены)
  $regexp = 'title=\"https:\/\/www.periscope.tv\/w\/(.*)\" ><span';
  preg_match_all("/$regexp/", $input, $matches);
  $matches = array_unique($matches[1]);
?>
  <script type="text/javascript">
    //Найденные токены (обновляются после перезагрузки):
    var tokens = <?php echo json_encode($matches) ?>;
    
    var count = 0;
    tokens.forEach(function(token) {
      
      //Получаем JSON трансляции:
      var url = 'https://api.periscope.tv/api/v2/getAccessPublic?broadcast_id='+token;
      $.post(location.href,{periscope: url},
      function(jsonData) {
        
        jsonData = JSON.parse(jsonData); 
        
        //Проверяем, есть ли ссылка на трансляцию:
        if ('hls_url' in jsonData && count < 10) {
          var id = 'player'+count;
          count++;
          
          //DIV для плеера:
          $('body').append('<div id="'+id+'"></div>');
          var container = document.getElementById(id);
          
          //Настройки плеера
          //Запрещаем встраивание:
          flowplayer.conf.embed = false;
          
          flowplayer(function(api, root) {
            
            //Меняем сообщения плеера:
            api.on("error", function(e, api, error) {
              api.error = api.loading = false; // reset state
              $(".fp-message", root).html('<h2>Трансляция завершена</h2>');
            });
            
            //Добавляем ссылку на канал трансляции:
            api.on("load", function() {
              var channel = 'https://www.periscope.tv/w/'+token;
              $(".fp-context-menu ul li:nth-child(2)", root)
                .html('<a target="_blank" href="'+channel+'">Канал трансляции</a>');
            });
          });

          //Параметры плеера:
          flowplayer(container, {
            live: true,
            ratio: 1.1778, //вертикальная ориентация (portrait), aspect ratio: 9:16 
            autoplay: true,
            clip: {
              sources: [
                {
                  type: "application/x-mpegurl",
                  src:  jsonData.hls_url 
                }
              ]
            }
          });
        }
      });
    });
  </script>
  </div>  
</body>
</html>