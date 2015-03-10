# 关于
实现了基于开源版本二维码的一次封装，提供SAE PHP Runtime 二维码服务，SAE的开发者们可以更轻松的使用二维码服务。

# 如何使用
- 上传src目录的代码到你的代码空间
- 包含类库文件saeqrcode.class.php
- 尽情的使用API

# 使用demo
**使用以下例子，你需要创建一个名叫public的Storage Domain。**
```php
<?php
//二维码名片，格式参考：http://en.wikipedia.org/wiki/VCard
$vCard  = 'BEGIN:VCARD'.PHP_EOL;
$vCard .= 'VERSION:4.0'.PHP_EOL;
$vCard .= 'FN:倒流'.PHP_EOL;
$vCard .= 'ORG:SINA Inc'.PHP_EOL;
$vCard .= 'TITLE:攻城师'.PHP_EOL;
$vCard .= 'TEL;WORK;VOICE:(010)62676155'.PHP_EOL;
$vCard .= 'TEL;HOME;VOICE:(010)88889999'.PHP_EOL;
$vCard .= 'TEL;TYPE=cell:18600005940'.PHP_EOL;
$vCard .= 'ADR;TYPE=work;LABEL="Office":理想国际大厦17层;北四环西路58号;海淀区;北京市;中国;100089'.PHP_EOL;
$vCard .= 'EMAIL:979137@qq.com'.PHP_EOL;
$vCard .= 'END:VCARD';
//注：不同的扫描工具解码方式不一样，所以不是所有的二维码扫描工具都能唤起相关的功能
$types  = array(
    'vCard'  => $vCard,
    'url'    => 'http://sae.sina.com.cn',
    'tel'    => 'tel:18600005940',
    'smsto'  => 'smsto:18600005940:晚上继续嗨皮',
    'mailto' => 'mailto:979137@qq.com?subject='.urlencode('恭喜发财').'&body='.urlencode('红包拿来'),
    'skype'  => 'skype:'.urlencode('Skype用户名').'?call',
);

$qr = new SaeQRcode();
//设置二维码生成参数
//二维码内容数据
$qr->data   = $types['vCard'];
//容错率：L(7%)、M(15%)、Q(25%)、H(30%)，默认M，了解：http://baike.baidu.com/view/4144600.htm
$qr->level  = 'L';
//二维码宽高（包含间距），为保证二维码更易识别，请尽量保持二维码为正方形，即宽高大致相等，默认200*200
$qr->width  = 300;
$qr->height = 300;
//二维码图片边缘间距值，值越大，间距越宽，可自由调整，默认0
$qr->margin = 1;
//在二维码正中间放置icon，默认为空，即不放置，支持绝对与相对地址
$qr->icon   = __DIR__ . '/logo.png';
$qr->icon   = 'logo.png';
//生成二维码图片，成功返回文件绝对地址（放在了SAE_TMP_PATH），失败返回false
$file = $qr->build();
if (!$file) {
    var_dump($qr->errno(), $qr->errmsg());
    exit;
}

//直接输出图片
//header('Content-Type: image/png');
//exit(file_get_contents($file));

//根据实际需求，可上传至Storage
$name = 'test/'.pathinfo($file, PATHINFO_BASENAME);
$domain = 'public';
$st = new SaeStorage();
$st->upload($domain, $name, $file);
$url = sprintf('http://%s-%s.stor.sinaapp.com/%s', $_SERVER['HTTP_APPNAME'], $domain, $name);
echo '<img src="'.$url.'">';
?>
```

# 联系我们
- 邮箱：saeadmin@sinacloud.com
- 微博私信：[@SinaAppEngine](http://weibo.com/saet)