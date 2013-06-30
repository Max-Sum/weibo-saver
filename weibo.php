<?php
/**

	@name 新浪微博抓取器
	@version 0.2
	@author Max Sum
	@description 使用新浪微博博客挂件定时抓取（crontab）指定ID的用户的微博，保存在cache目录，以rss格式输出。
	@license http://www.gnu.org/licenses/agpl-3.0.html

**/
	require('simple_html_dom.php');
	$id = $_GET['id'];
	$file = file_get_contents('http://v.t.sina.com.cn/widget/widget_blog.php?height=2000&skin=wd_01&showpic=1&uid='.$id);
	$html = str_get_html($file);
	
	//读取/创建缓存
	if(is_file("cache/$id")){
		$cache = unserialize(file_get_contents("cache/$id"));
	}else{
		$cache = array();
	}

	//循环获取微博
	foreach($html->find('div.wgtCell') as $w){
		$weibo = $w->children(0);
		$txt = $weibo->children(0)->innertext;
		$time = $weibo->children(1)->children(0)->children(0)->innertext;
		$link = $weibo->children(1)->children(0)->children(0)->href;
		
		//图片变大
		$txt = str_replace('thumbnail','large',$txt);
		
		//时间处理
		if(mb_substr($time,0,2,'utf-8') == '今天'){
			$time = strptime($time, '%H:%M');
			$time = mktime($time['tm_hour'],$time['tm_min'],0);
		}elseif(mb_substr($time,-3,3,'utf-8') == '分钟前'){
			$time = mb_substr($time,0,-3,'utf-8');
			$time = time()-60*$time;
		}elseif(!strpos($time,'月')){
			$time = strptime($time, '%Y-%m-%d %H:%M');
			$time = mktime($time['tm_hour'],$time['tm_min'],0,$time['tm_mon'],$time['tm_mday'],$time['tm_year']+1900);
		}else{
			$time = strptime($time, '%m月%d日 %H:%M');
			$time = mktime($time['tm_hour'],$time['tm_min'],0,$time['tm_mon'],$time['tm_mday']);
		}
		
		//插入数组
		if(strpos(serialize($cache),$link)){
			continue;
		}else{
			$cache[] = array('txt'=>$txt,'time'=>$time,'link'=>$link);
		}
	}
	
	//修复bug。。删除没有time数据的微博重新抓取
  for($i=count($cache)-1;$i>=0;$i--){
		$weibo = $cache[$i];
		if(!$weibo['time']) unset($cache[$i]);
	}
	
	//按时间从早到晚排列
	function fsort($a, $b){
		if($a['time']==$b['time']) return 0;
		return ($a['time'] < $b['time'])? -1:1;
	}
	usort($cache, "fsort");
	
	//超过100删除
	while(count($cache)>100){
		array_shift($cache);
	}
	
	//保存缓存
	file_put_contents("cache/$id",serialize($cache));
	
	//设置nooutput方便crontab
	if(!isset($_GET['nooutput'])){
		header("Content-type:application/xml");
		?>
		
<rss version="2.0">
        <channel>
                <title>rssfeed</title>
                <link>rssfeed</link>
                <description>rssfeed</description>
                <language>zh-cn</language> 
                
<?php
  for($i=count($cache)-1;$i>=0;$i--){
		$weibo = $cache[$i];
?>
     <item>
         <title>微博</title>
         <description><![CDATA[<?php echo $weibo['txt']; ?>]]></description>
         <pubDate><?php echo date(DATE_RFC822,$weibo['time']); ?></pubDate>
         <guid><?php echo $weibo['link']; ?></guid>
         <link><?php echo $weibo['link']; ?></link>
      </item>
      
     </channel>
</rss>
<?php
	}
}
?>