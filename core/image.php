<?php
$c_tools->fw_error('c_image',false,false,'imagick');
global $c_image;
$c_image=new c_image();

/**
 * Инструменты для работы с изображениями
 * Должен быть установлен php5-imagick
 */
class c_image{
    /**
     * @name Основные методы
     * @{
     */
    /**
     * Изменение размера изображения
     *
     * @param $infile исходный файл
     * @param $outfile сохраняемый файл
     * @param int $width ширина - если "0" вычислить пропорционально высоте
     * @param int $height высота - если "0" вычислить пропорционально ширине
     * @param bool $bestfit улучшение четкости изображения
     * @param int $corner закругления улов
     * @param int $quality качество
     * @param bool $sharp резкость
     *
     * @return bool|string
     * <ul>
     *    <li> false - ошибок нет
     *    <li> "c_image_rsz_error_read" - не удалость прочитать исходный файл
     *    <li> "c_image_rsz_error_rsz_scale" - не удалось изменить размер
     *    <li> "c_image_rsz_error_rsz_sharp" - не удалось изменить резкость
     *    <li> "c_image_rsz_error_rsz_corner" - не удалось закруглить углы
     *    <li> "c_image_rsz_error_rsz_write" - не удалось записать результат
     * </ul>
     */
    public function resize($infile,$outfile,$width=0,$height=0,$bestfit=true,$corner=0,$quality=100,$sharp=true){
        $im=new Imagick();
        if(!$im->readImage($infile)) return 'c_image_rsz_error_read';
        $type=strtolower($im->getImageFormat());

        if(in_array($type,array('gif','jpeg','jpg','png','bmp'))){

            if($width==0||$height==0){
                $size=$im->getImageGeometry();
                $size=$this->convertSize($width,$height,$size);
                $width=$size['width'];
                $height=$size['height'];
            }

            $im->setImageCompressionQuality($quality);
            if(!$im->scaleImage($width,$height,$bestfit)){
                $im->destroy();
                return 'c_image_rsz_error_rsz_scale';
            }
            if($sharp){
                $colors=$im->getImageColors();
                $sharp=false;
                $cw=($width>0)?$width:$height;
                $ch=($height>0)?$height:$width;
                $cs=(($cw+$ch)/2)/$colors;
                if($cs<0.03) $sharp=true;
                if($sharp){
                    if(!$im->sharpenImage(1,4)){
                        $im->destroy();
                        return 'c_image_rsz_error_sharp';
                    }
                }
            }
            if($corner){
                if(!$im->roundCorners($corner,$corner)){
                    $im->destroy();
                    return 'c_image_rsz_error_corner';
                }
            }
            if(!$im->writeImage($outfile)){
                $im->destroy();
                return 'c_image_rsz_error_write';
            }
        }

        return false;
    }
    public function convertSize($width,$height,$size){
        if(!$width&&$height){
            $k=$height/$size['height'];
            if(($k*10000)<10000){
                $width=round($size['width']*$k);
                $height=round($size['height']*$k);
            }
            else{
                $width=$size['width'];
                $height=$size['height'];
            }
        }
        elseif(!$height&&$width){

            $k=$width/$size['width'];
            if(($k*10000)<10000){
                $width=round($size['width']*$k);
                $height=round($size['height']*$k);
            }
            else{
                $width=$size['width'];
                $height=$size['height'];
            }
        }
        else{
            $width=$size['width'];
            $height=$size['height'];
        }
        return array('width'=>$width,'height'=>$height);
    }

    /**
     * Изменение размера изображения с обрезкой краев
     * Все что выходит за указанные размеры равномерно обрезается.
     *
     * @param $infile исходный файл
     * @param $outfile сохраняемый файл
     * @param int $width ширина - если "0" вычислить пропорционально высоте
     * @param int $height высота - если "0" вычислить пропорционально ширине
     * @param int $corner закругления улов
     * @param int $quality качество
     *
     * @return bool|string
     * <ul>
     *    <li> false - ошибок нет
     *    <li> "c_image_rsz_error_read" - не удалость прочитать исходный файл
     *    <li> "c_image_rsz_error_rsz_crop" - не удалось изменить размер с обрезкой
     *    <li> "c_image_rsz_error_rsz_corner" - не удалось закруглить углы
     *    <li> "c_image_rsz_error_rsz_write" - не удалось записать результат
     * </ul>
     **/
    public function resizeCrop($infile,$outfile,$width=0,$height=0,$corner=0,$quality=100){
        $im=new Imagick();
        if(!$im->readImage($infile)){
            $im->destroy();
            return 'c_image_rsz_error_read';
        }
        $type=strtolower($im->getImageFormat());

        if(in_array($type,array('gif','jpeg','jpg','png','bmp'))){
            $im->setImageCompressionQuality($quality);
            if(!$im->cropThumbnailImage($width,$height)){
                $im->destroy();
                return 'c_image_rsz_error_crop';
            }
            if($corner){
                if(!$im->roundCorners($corner,$corner)){
                    $im->destroy();
                    return 'c_image_rsz_error_corner';
                }
            }
            if(!$im->writeImage($outfile)){
                $im->destroy();
                return 'c_image_rsz_error_write';
            }
        }

        return false;
    }

    /**
     * Создание изображение с отступами и фоном, в местах, где ширины или высоты изображения не хватает.
     *
     * @param $infile исходный файл
     * @param $outfile сохраняемый файл
     * @param int $width ширина - если "0" вычислить пропорционально высоте
     * @param int $height высота - если "0" вычислить пропорционально ширине
     * @param int $padding отступ от краев
     * @param string $color цвет фона
     *
     * @return bool|string
     * <ul>
     *    <li> false - ошибок нет
     *    <li> "c_image_rsz_error_read" - не удалость прочитать исходный файл
     *    <li> "c_image_rsz_error_rsz_composite" - не удалось сформировать изображение
     *    <li> "c_image_rsz_error_rsz_write" - не удалось записать результат
     * </ul>
     */
    public function thumbnail($infile,$outfile,$width,$height,$padding=0,$color='white'){
        $widthIm=$width-$padding;
        $heightIm=$height-$padding;
        $resize=$this->resize($infile,$outfile,$widthIm,$heightIm,true,0,100,false);
        if($resize!==false)return $resize;
        $im=new Imagick();
        if(!$im->readImage($outfile)){
            $im->destroy();
            return 'c_image_tn_error_read';
        }

        $canvas=new Imagick();
        $canvas->newImage($width,$height,$color,'jpg');
        $geometry=$im->getImageGeometry();
        $x=($width-$geometry['width'])/2;
        $y=($height-$geometry['height'])/2;
        if(!$canvas->compositeImage($im,imagick::COMPOSITE_OVER,$x,$y)){
            $canvas->destroy();
            $im->destroy();
            return 'c_image_tn_error_composite';
        }
        if(!$canvas->writeImage($outfile)){
            $canvas->destroy();
            $im->destroy();
            return 'c_image_tn_error_write';
        }

        return false;
    }

    /**
     * Проверка состояния загруженного файла на ошибку
     *
     * @param $name имя тега file
     *
     * @return bool|string
     * <ul>
     * <li> false - ошибок нет
     * <li> c_image_tmp_error_num_[1-4] - ошибки при загрузке изображения
     * <li> c_image_tmp_none - изображение вообше не загружалось
     * </ul>
     */
    public function error($name){
        $numerr=@$_FILES[$name]['error'];
        if(!empty($numerr)) return $error='c_image_tmp_error_num_'.(($numerr>4)?4:$numerr);
        elseif(@empty($_FILES[$name]['tmp_name'])||@$_FILES[$name]['tmp_name']=='none') return $error='c_image_tmp_none';
        else return false;
    }
    /**
     * @}
     * @name Обработка загруженных изображений
     * @{
     */
    /**
     * Пакетная обработка загруженного файла с использованием метода thumbnail
     *
     * @param $folder - папка, где будет генерироваться пакет изображений
     * @param $imgN - имя тега, загружаемого изображения
     * @param array $imgA массив обектов изображений в виде $defImg['940x350']=(object)array('w'=>940,'h'=>350);, где: 940x350 - название файла, 940 - ширина, 350 - высота
     * @param string $name имя пакета
     * @param string $delimer разделитель (например, если пакетом будет папка, тогда раздеитель должен быть '/')
     * @param bool $thP - отступ
     * @param bool $thC - цвет
     *
     * @return bool|string false - ошибок нет или имена ошибок из основных методов
     */
    function upload($folder,$imgN,$imgA=array(),$name='',$delimer='',$thP=false,$thC=false){
        global $c_tools;
        $error=$this->error($imgN);
        if(!$error){
            $uploadPath=PATH.UPLOADS_PATH.$folder;
            $c_tools->makefolder($uploadPath);
            foreach($imgA as $k=>$v){
                if($thP===false&&$thC===false) $resize=$this->resize($_FILES[$imgN]['tmp_name'],$uploadPath.'/'.$name.$delimer.$k.'.jpg',$v->w,$v->h);
                else $resize=$this->thumbnail($_FILES[$imgN]['tmp_name'],$uploadPath.'/'.$name.$delimer.$k.'.jpg',$v->w,$v->h,$thP,$thC);
                if($resize!==false) return $resize;
            }

            return false;
        }
        else return $error;
    }

    /**
     * Пакетная обработка загруженного файла с использованием метода resizeCrop
     *
     * @param $folder - папка, где будет генерироваться пакет изображений
     * @param $imgN - имя тега, загружаемого изображения
     * @param array $imgA массив обектов изображений в виде $defImg['940x350']=(object)array('w'=>940,'h'=>350);, где: 940x350 - название файла, 940 - ширина, 350 - высота
     * @param string $name имя пакета
     * @param string $delimer разделитель (например, если пакетом будет папка, тогда раздеитель должен быть '/')
     *
     * @return bool|string false - ошибок нет или имена ошибок из основных методов
     */
    function uploadCrop($folder,$imgN,$imgA=array(),$name='',$delimer=''){
        global $c_tools;
        $error=$this->error($imgN);
        if(!$error){
            $uploadPath=PATH.UPLOADS_PATH.$folder;
            $c_tools->makefolder($uploadPath);
            foreach($imgA as $k=>$v){
                $resize=$this->resizeCrop($_FILES[$imgN]['tmp_name'],$uploadPath.'/'.$name.$delimer.$k.'.jpg',$v->w,$v->h);
                if($resize!==false) return $resize;
            }

            return false;
        }
        else return $error;
    }
    /**
     * @}
     * @name Другие методы
     * @{
     */
    /**
     * Удаление пакета изображений
     *
     * @param $folder - папка, где будут удаляться изображения
     * @param array $imgA массив обектов изображений в виде $defImg['940x350']=(object)array('w'=>940,'h'=>350);, где: 940x350 - название файла, 940 - ширина, 350 - высота
     * @param string $name имя пакета
     * @param string $delimer разделитель (например, если пакетом будет папка, тогда раздеитель должен быть '/')
     */
    function delete($folder,$imgA=array(),$name='',$delimer=''){
        if(is_string($name)){
            $uploadPath=PATH.$folder;
            foreach($imgA as $k=>$v){
                $filename=$uploadPath.'/'.$name.$delimer.$k.'.jpg';
                if(file_exists($filename)) unlink($filename);
            }
        }
        elseif(is_array($name)&&count($name)>0){
            foreach($name as $k=>$v){
                $this->delete($folder,$imgA,$v,$delimer);
            }
        }
    }
    /**
     * @}
     */
}
