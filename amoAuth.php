<?php
include_once 'init.php';

use \AmoIntegrations\AmoSettings;

$amoSettings = AmoSettings::getInstance();

if (empty($amoSettings)) {
    echo 'Wrong configs';
    exit;
}


if (!isset($_GET['token']) || $_GET['token'] != $amoSettings->token) {
    echo 'Incorrect token';
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Амо</title>
</head>

<body>
    <div>
        <button id="amoAuth_btn">Authenticate</button>
    </div>
    <script>
        var button = document.querySelector('button');
        button.addEventListener('click', (event) => {
            amoAuth();
        });

        function amoAuth() {
            var popup;
            auth();

            function auth() {
                popup = window.open('https://www.amocrm.ru/oauth?client_id=<?php echo $amoSettings->client_id; ?>&mode=post_message', 'Предоставить доступ', 'scrollbars, status, resizable, width=750, height=580');
            }

            window.addEventListener('message', updateAuthInfo);

            function updateAuthInfo(e) {
                if (e.data.error !== undefined) {
                    console.log('Ошибка - ' + e.data.error)
                    var tag = '<div><span style="color:red">Произошла ошибка. Проверьте настройки</span></div';
                } else {
                    console.log('Токены успешно сгенерированы');
                    var tag = '<div><span style="color:green">Токены успешно сгенерированы</span></div';
                }
                button.insertAdjacentHTML('afterend', tag);

                popup.close();
            }
        }
    </script>

</body>

</html>