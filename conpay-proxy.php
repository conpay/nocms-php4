<?php
// Подключаем скрипт с классом ConpayProxyModelPhp4, выполняющим бизнес-логику
require './ConpayProxyModelPhp4.php';
// Создаем объект класса ConpayProxyModelPhp4
$proxy = new ConpayProxyModelPhp4;
// Устанавливаем свой идентификатор продавца
$proxy->setMerchantId(94);
// Устанавливаем кодировку, используемую на сайте (по-умолчанию 'UTF-8')
$proxy->setCharset('WINDOWS-1251');
// Выполняем запрос, выводя его результат
echo $proxy->sendRequest();