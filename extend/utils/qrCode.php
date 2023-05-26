<?php
namespace utils;
require_once \think\facade\App::getRootPath().'extend/phpqrcode/phpqrcode.php';
class qrCode
{
    /**
     * phpqrcode php生成二维码
     * $frame string 二维码内容
     * $filename string|false 默认为否，不生成文件，只将二维码图片返回，否则需要给出存放生成二维码图片的路径
     * $level 默认为L，这个参数可传递的值分别是L(QR_ECLEVEL_L，7%)，M(QR_ECLEVEL_M，15%),Q(QR_ECLEVEL_Q，25%)，H(QR_ECLEVEL_H，30%)。这个参数控制二维码容错率，不同的参数表示二维码可被覆盖的区域百分比。
     * $size int 生成二维码的区域大小点的大小：1到10
     * $margin int 图片留白大小
     * $saveandprint string 保存二维码图片并显示出来，$outfile必须传递图片路径
     */
    public function qrcode($frame, $filename = false, $level = 'L', $size = 5, $margin = 2, $saveandprint=false){
        header('Content-Type: image/png');
        $qrcode = new \QRcode();
        ob_clean();
        $png = $qrcode->png($frame, $filename , $level , $size , $margin , $saveandprint);
        return $png;
    }
    /**
     * 生成二维码以base64输出,
     * $frame 二维码内容
     * 参数同qrcode………………
     */
    public function qrcode64($frame, $level = 'L', $size = 5, $margin = 2){
        $QRcode = new \QRcode();
        ob_start(); // 在服务器打开一个缓冲区来保存所有的输出
        $QRcode->png($frame,false,$level,$size,$margin);
        $imageString = base64_encode(ob_get_contents());
        ob_end_clean(); //清除缓冲区的内容，并将缓冲区关闭，但不会输出内容
        return "data:image/jpg;base64,".$imageString;
    }

}
