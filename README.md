weibo-saver
===========

Automatically save someone's weibo. No more worry about his/her deletion.

Set up:
 Put all these files to a directory and set a cronjob up.

 run crontab -e
 and add a cronjob running per serval minutes:
 crul http://xxx.xxx/xxxxxx/weibo.php?id=<his/her weibo id>&nooutput=1;

 Now you can read all his/her weibo using RSS reader:
 http://xxx.xxx/xxxxxx/weibo.php?id=<his/her weibo id>
  

