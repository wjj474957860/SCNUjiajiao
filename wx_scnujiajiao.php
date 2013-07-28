<?php
//引入simple_html_dom.php来解释网页
include("simple_html_dom.php");

//第一部分：define your token
define("TOKEN", "scnujiajiao");

//第三部分：创建类wechatCallbackapiTest实例对象
$wechatObj = new wechatCallbackapiTest();

//第四部分：调用类的valid方法,确认之后在进行回应。
$wechatObj->valid();


//第二部分：声明类wechatCallbackapiTest
class wechatCallbackapiTest
{
    //验证是否由微信服务器发送过来的信息
    public function valid()
    {
        //$echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
            //echo $echoStr;
            //exit;

            //若正确，调用类的方法responseMsg()。
            $this->responseMsg();
        }else{
            //若不正确，返回此文本。
            echo "Unknown Request!";
        }
    }

    public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        //extract post data
        if (!empty($postStr)){
                
                $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

                //获取MsgType的值
                $msgType = trim($postObj->MsgType);

                switch($msgType)
                {
                    //文字信息
                    case "text":
                        $resultStr = $this->handleText($postObj);
                        break;
                    //事件信息
                    case "event":
                        $resultStr = $this->handleEvent($postObj);
                        break;
                    default:
                        $resultStr = "Unknown Msg Type: ".$msgType;
                        break;
                }
                echo $resultStr;
        }else {
            echo "";
            exit;
        }
    }
    
    //处理文本消息
    public function handleText($postObj)
    {
        $fromUsername = $postObj->FromUserName;
        $toUsername = $postObj->ToUserName;
        $keyword = trim($postObj->Content);
        //$time = time();
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>0</FuncFlag>
                    </xml>"; 
        $linkTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <ArticleCount>%d</ArticleCount>
                    <Articles>
                    <item>
                    <Title><![CDATA[%s]]></Title>
                    <Description><![CDATA[%s]]></Description>
                    <PicUrl><![CDATA[%s]]></PicUrl>
                    <Url><![CDATA[%s]]></Url>
                    </item>
                    </Articles>
                    <FuncFlag>%d</FuncFlag>
                    </xml> ";            
        if(!empty( $keyword ))
        {
            //华南师范大学勤工助学家教信息
            $Url = "http://www.scnuqg.com/Main/findfamilteach2.asp";
            $html = file_get_html($Url);
            switch($keyword){
                case 'f':
                case 'F':
                //判断网页是否存在
                if($html){
                    $msgType = "news";
                    $PicUrl = "http://1.scnujiajiao.sinaapp.com/img/qgjiajiao.jpg";
                    $Title = "勤工助学家教信息";
                    //可获得“现有家教信息(X条)”
                    $Description = $html->find("strong font",0)->plaintext;
                    $resultStr = sprintf($linkTpl, $fromUsername, $toUsername, time(), $msgType, 1, $Title, $Description, $PicUrl, $Url, 1);
                }else{
                    $msgType = "text";
                    $contentStr = "Error: 404 !"."\n"."o(>﹏<)o 目前木有找到家教信息！";
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, time(), $msgType, $contentStr);
                }
                break;
                case 'l':
                case 'L':
                    $msgType = "text";
                    $contentStr = "请您留言或反馈，谢谢~";
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, time(), $msgType, $contentStr);
                break;
                default:
                $msgType = "text";
                $contentStr = "目前平台功能有:\n【f】查找家教信息; 输入字符“ f ” (大写也可)进行查找家教信息。 \n【l】留言或反馈; 输入字符“ l ” (大写也可)进行留言或反馈。\n更多功能开发中…敬请留意！";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, time(), $msgType, $contentStr); 
                break;
            }
            echo $resultStr;    
        }else{
            echo "请您输入命令...";
        }
    }

    //处理事件消息
    public function handleEvent($object)
    {
        $contentStr = "";
        switch ($object->Event)
        {
            case "subscribe":
                $contentStr = "感谢您关注【华师家教】"."\n"."微信号：SCNUjiajiao"."\n"."使用【华师家教】可很方便地查询到华南师范大学勤工助学、华师新陶园等渠道网站的家教信息，可以第一时候知道有合适自己的家教，并很快地找到自己称心满意的家教！"."\n"."目前平台功能有:\n【f】查找家教;\n【l】留言或反馈; \n更多功能开发中…敬请留意！";
                break;
            default :
                $contentStr = "Unknown Event: ".$object->Event;
                break;
        }
        $resultStr = $this->responseText($object, $contentStr);
        return $resultStr;
    }

    //反馈文本信息
    public function responseText($object, $content, $flag=0)
    {
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>%d</FuncFlag>
                    </xml>";
        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content, $flag);
        return $resultStr;
    }

    //检查签名正确与否
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];    
                
        $token = TOKEN;
        //加密/校验流程
        $tmpArr = array($token, $timestamp, $nonce);
        //1. 将token、timestamp、nonce三个参数进行字典序排序
        sort($tmpArr);
        //2. 将三个参数字符串拼接成一个字符串进行sha1加密
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        //3. 开发者获得加密后的字符串可与signature对比，标识该请求来源于微信
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
}

?>