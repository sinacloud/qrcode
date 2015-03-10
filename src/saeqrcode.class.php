<?php
/**
 * SAE 二维码服务.
 *
 * <code>
 * <?php
 * //二维码名片，格式参考：http://en.wikipedia.org/wiki/VCard
 * $vCard  = 'BEGIN:VCARD'.PHP_EOL;
 * $vCard .= 'VERSION:4.0'.PHP_EOL;
 * $vCard .= 'FN:倒流'.PHP_EOL;
 * $vCard .= 'ORG:SINA Inc'.PHP_EOL;
 * $vCard .= 'TITLE:攻城师'.PHP_EOL;
 * $vCard .= 'TEL;WORK;VOICE:(010)62676155'.PHP_EOL;
 * $vCard .= 'TEL;HOME;VOICE:(010)88889999'.PHP_EOL;
 * $vCard .= 'TEL;TYPE=cell:18600005940'.PHP_EOL;
 * $vCard .= 'ADR;TYPE=work;LABEL="Office":理想国际大厦17层;北四环西路58号;海淀区;北京市;中国;100089'.PHP_EOL;
 * $vCard .= 'EMAIL:979137@qq.com'.PHP_EOL;
 * $vCard .= 'END:VCARD';
 * //注：不同的扫描工具解码方式不一样，所以不是所有的二维码扫描工具都能唤起相关的功能
 * $types  = array(
 *     'vCard'  => $vCard,
 *     'url'    => 'http://sae.sina.com.cn',
 *     'tel'    => 'tel:18600005940',
 *     'smsto'  => 'smsto:18600005940:晚上继续嗨皮',
 *     'mailto' => 'mailto:979137@qq.com?subject='.urlencode('恭喜发财').'&body='.urlencode('红包拿来'),
 *     'skype'  => 'skype:'.urlencode('Skype用户名').'?call',
 * );
 * 
 * $qr = new SaeQRcode();
 * //设置二维码生成参数
 * //二维码内容数据
 * $qr->data   = $types['vCard'];
 * //容错率：L(7%)、M(15%)、Q(25%)、H(30%)，默认M，了解：http://baike.baidu.com/view/4144600.htm
 * $qr->level  = 'L';
 * //二维码宽高（包含间距），为保证二维码更易识别，请尽量保持二维码为正方形，即宽高大致相等，默认200*200
 * $qr->width  = 300;
 * $qr->height = 300;
 * //二维码图片边缘间距值，值越大，间距越宽，可自由调整，默认0
 * $qr->margin = 1;
 * //在二维码正中间放置icon，默认为空，即不放置，支持绝对与相对地址
 * $qr->icon   = __DIR__ . '/logo.png';
 * $qr->icon   = 'logo.png';
 * //生成二维码图片，成功返回文件绝对地址（放在了SAE_TMP_PATH），失败返回false
 * $file = $qr->build();
 * if (!$file) {
 *     var_dump($qr->errno(), $qr->errmsg());
 *     exit;
 * }
 *
 * //直接输出图片
 * //header('Content-Type: image/png');
 * //exit(file_get_contents($file));
 * 
 * //根据实际需求，可上传至Storage
 * $name = 'test/'.pathinfo($file, PATHINFO_BASENAME);
 * $domain = 'public';
 * $st = new SaeStorage();
 * $st->upload($domain, $name, $file);
 * $url = sprintf('http://%s-%s.stor.sinaapp.com/%s', $_SERVER['HTTP_APPNAME'], $domain, $name);
 * echo '<img src="'.$url.'">';
 * ?>
 * </code>
 *
 * 错误码参考：<br />
 *  - errno: 0         成功<br />
 *  - errno: -1        二维码数据为空<br />
 *  - errno: -2        容错率参数错误<br />
 *  - errno: -3        宽高参数错误，必须为数字<br />
 *  - errno: -4        生成二维码出错<br />
 *  - errno: -10       二维码文件不存在或不支持的文件格式<br />
 *  - errno: -11       icon文件不存在或不支持的文件格式<br />
 *
 * @author 979137@qq.com
 * @copyright ©2015, Sina App Engine.
 * @version $Id$
 * @package sae
 */

class SaeQRcode extends SaeObject 
{
    private $accessKey = '';
    private $secretKey = '';
    private $errMsg    = 'success';
    private $errNum    = 0;

    //二维码配置参数
    public $data       = '';
    public $level      = 'M';
    public $width      = 200;
    public $height     = 200;
    public $margin     = 0;
    public $icon       = '';

    //生成的二维码文件
    private $code      = '';
    
    /**
     * 生成二维码图片 
     * 
     * @desc
     * 
     * @access public
     * @return void
     * @exception none
     */
    public function build() 
    {
        static $qrcode = false;
        if (!$qrcode) {
            include(__DIR__.'/phpqrcode.php');
            $qrcode = true;
        }
        if (trim($this->data) == '') {
            $this->errNum = -1;
            $this->errMsg = 'data cannot be empty!';
            return false;
        } elseif (!in_array($this->level, array('L','M','Q','H'))) {
            $this->errNum = -2;
            $this->errMsg = 'level optional values: L, M, Q, H';
            return false;
        } elseif (!is_numeric($this->width) || !is_numeric($this->height)) {
            $this->errNum = -3;
            $this->errMsg = 'width and height parameter error';
            return false;
        }
        $this->code = SAE_TMP_PATH . md5((microtime(true)*10000).uniqid(time())) . '.png';
        try {
            defined('QRCODE_IMG_W') or define('QRCODE_IMG_W', $this->width);
            defined('QRCODE_IMG_H') or define('QRCODE_IMG_H', $this->height);
            QRcode::png($this->data, $this->code, $this->level, 3, $this->margin);
        } catch(Exception $e) {
            $this->errNum = -4;
            $this->errMsg = $e->getMessage();
            return false;
        }
        if (trim($this->icon) != '') {
            return $this->iconCover() ? $this->code : false;
        }
        return $this->code;
    }

    /**
     * icon覆盖
     * 
     * @desc
     * 
     * @access public
     * @return boolean
     * @exception none
     */
    public function iconCover() 
    {
        if (!is_file($this->code) || $this->fileType($this->code) != 'png') {
            $this->errNum = -10;
            $this->errMsg = 'QRcode file does not exist or file type is not supported(Only allow PNG)';
            return false;
        }
        //远程icon，先下载到本地
        if (filter_var($this->icon, FILTER_VALIDATE_URL)) {
            //TODO..
        }
        if (!is_file($this->icon) || !in_array($this->fileType($this->icon), array('png','jpg','gif'))) {
            $this->errNum = -11;
            $this->errMsg = 'icon file does not exist or file type is not supported(Only allow PNG,JPG,GIF)';
            return false;
        }
        $codeData = file_get_contents($this->code);
        $iconData = file_get_contents($this->icon);
        $code = imagecreatefromstring($codeData);
        $icon = imagecreatefromstring($iconData);
        list($code_w, $code_h) = array(imagesx($code), imagesy($code));
        list($icon_w, $icon_h) = array(imagesx($icon), imagesy($icon));
        //目标宽高（等比例缩小）
        $icon_code_w = $code_w / 5;
        $scale = $icon_w / $icon_code_w;
        $icon_code_h = $icon_h / $scale;
        //目标XY坐标（将icon置于二维码正中间）
        $dst_x = ($code_w - $icon_code_w) / 2;
        $dst_y = ($code_h - $icon_code_h) / 2;
        imagecopyresampled($code, $icon, $dst_x, $dst_y, 0, 0, $icon_code_w, $icon_code_h, $icon_w, $icon_h);
        return imagepng($code, $this->code);
    }
	
    /**
     * 获取错误信息 
     * 
     * @desc
     * 
     * @access public
     * @return string
     * @exception none
     */
    public function errmsg()
    {
        $ret = $this->errMsg;
        $this->errMsg = 'Success';
        return $ret;
    }

    /**
     * 获取错误码 
     * 
     * @desc
     * 
     * @access public
     * @return int
     * @exception none
     */
    public function errno()
    {
        $ret = $this->errNum;
        $this->errNum = 0;
        return $ret;
    }

    /**
     * 取二进制文件头快速准确判断文件类型
     * 
     * @desc
     * 
     * @access public
     * @params $file 要判断的文件，支持相对和绝对路径
     * @return void
     * @exception none
     */
    public function fileType($file) 
    {
        $filepath = realpath($file);
        $filetype = array(
            7790=>'exe', 7784=>'midi',
            8075=>'zip', 8297=>'rar',
            7173=>'gif', 6677=>'bmp', 13780=>'png', 255216=>'jpg'
        );
        if (!($fp = @fopen($filepath, 'rb'))) return false;
        $bin = fread($fp, 2);
        fclose($fp);
        $str_info = @unpack('C2chars', $bin);
        $str_code = intval($str_info['chars1'].$str_info['chars2']);
        return isset($filetype[$str_code]) ? $filetype[$str_code] : false;
    }
}
