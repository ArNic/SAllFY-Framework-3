<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="google-site-verification" content="uPddfV9BzPSzzRBHaCw7UClPB9KRTXGwlXX5KrW8Oz0" />
		<meta name='yandex-verification' content='61b840dfe071287c' />
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title><?=@SEO_TITLE?><?=($this->title&&SEO_TITLE)?' | ':''?><?=$this->title?></title>
        <? if(@$this->seoKey||@SEO_KEY){ ?><meta name="keywords" content="<?=(@$this->seoKey)?$this->seoKey:@SEO_KEY?>" /> <? } ?>
        <? if(@$this->seoDesc||@SEO_DESC){ ?><meta name="description" content="<?=(@$this->seoDesc)?$this->seoDesc:@SEO_DESC?>" /> <? } ?>
        <?=$this->javascript?><?=@$this->css?>
	</head>
	<body><?=$this->body?></body>
</html>