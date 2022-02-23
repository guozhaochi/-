<?php
/* 
 * 微信自动回复页面
 * Author:Mr.Lee(Felone)
 * Date:2014-01-13 22:04
 */
require("./hh/lib/base.php");
require("./hh/lib/db_sql.php");
require("./hh/lib/wechat.class.php");
$link=db_connect();
$henghao=new mysqlquery();
$uid=(int)$_GET['uid'];
$wr=$henghao->fetch1("select token from {$dbtbpre}wechat_setting where uid=$uid");
$token=$wr['token'];
//微信类
$wechat=new wechat(); 
$postobj=$GLOBALS["HTTP_RAW_POST_DATA"];//微信推送内容
$poststr=simplexml_load_string($postobj, 'SimpleXMLElement', LIBXML_NOCDATA);
$tousername=$poststr->ToUserName;//开发者微信号
$fromusername=$poststr->FromUserName;//发送方帐号
$createtime=$poststr->CreateTime;//消息创建时间 
$msgtype=$poststr->MsgType;//消息类型:text文本,语音为voice,视频为video,location地理位置,消息类型，link,image图片消息
$msgid=$poststr->MsgId;//消息id，64位整型

if($msgtype=='image')
{
    //图片消息
    $picurl=$poststr->PicUrl;//图片链接
}
if($msgtype=='text')
{
    //文本消息
    $content=$poststr->Content;//消息内容
    $keywords=$content;
}
if($msgtype=='voice')
{
    //语音消息
    $mediaid=$poststr->MediaId;//语音消息媒体id，可以调用多媒体文件下载接口拉取数据。
    $format=$poststr->Format;//语音格式，如amr，speex等
}
if($msgtype=='video')
{
    //视频消息
    $thumbmediaid=$poststr->ThumbMediaId;//视频消息缩略图的媒体id，可以调用多媒体文件下载接口拉取数据。
}
if($msgtype=='location')
{
    //地理位置消息
    $locationx=$poststr->Location_X;//地理位置维度
    $locationy=$poststr->Location_Y;//地理位置经度
    $scale=$poststr->Scale;//地图缩放大小
    $label=$poststr->Label;//地理位置信息
}
if($msgtype=='link')
{
    //链接消息
    $title=$poststr->Title;//消息标题
    $description=$poststr->Description;//消息描述
    $url=$poststr->Url;//消息链接
}

if($msgtype=='event')
{
    $event=$poststr->Event;
    $eventkey=$poststr->EventKey;
	
    if($event=='subscribe')
    {
        $wechat->subscribe();//订阅事件处理
    }
    //取消订阅
    if($event=='unsubscribe')
    {
        $wechat->unsubscribe();//取消订阅处理
    } 
	$kr=$henghao->fetch1("select keywords from {$dbtbpre}wechat_reply where id=$eventkey");
	$keywords=$kr['keywords'];
}

;
//关键字回复
$replyr=$henghao->fetch1("select type,description,id from {$dbtbpre}wechat_reply where uid=$uid and keywords='$keywords'");
if($replyr['type']==1)
{
    $wechat->save_message("类型:$msgtype<br/>关键字:$keywords",1);
    $wechat->reponse_text($replyr[description]);
    $wechat->save_message("类型:text<br/>内容:$replyr[description]",2);  
}elseif($replyr['type']==2)
{
    $sql=$henghao->query("select title,description,id,picurl,url from {$dbtbpre}wechat_news where type=$replyr[id] and uid=$uid");
    $i=0;
    $url=  GetDomain();
	$r['url']=$r['url']?$r['url']:($url.'/wap/show_news.php?id='.$r['id']);
    while($r=$henghao->fetch($sql))
    {
        $data[$i]="<item>
                                <Title><![CDATA[$r[title]]]></Title> 
                                <Description><![CDATA[$r[description]]]></Description>
                                <PicUrl><![CDATA[$url/$r[picurl]]]></PicUrl>
                                <Url><![CDATA[$r[url]]]></Url>
                            </item>";
        $i++;
    }
    $wechat->reponse_images($data);
}else
{
    $nr=$henghao->fetch1("select notfind from {$dbtbpre}wechat_setting where uid=$uid");
    $wechat->save_message($keywords,1);
    $wechat->reponse_text($nr[notfind]);//未找到内容提示!
    $wechat->save_message($nr[notfind],2);
}