<?php
require_once __DIR__ . '/config.php';
// 微信配置参数
define('WX_TOKEN', 'qweqwe');          // 原Token
define('WX_NEW_TOKEN', 'new_token');   // 新增Token
define('WX_APPID', 'your_appid');
define('WX_ENCODING_AESKEY', 'pvh2j7LuYWFFOWboCf0WNq4D2Pgnpe4MdUZASGjnYGw');

// 数据库配置

if (!isset($_GET['signature'], $_GET['timestamp'], $_GET['nonce'])) {
    header("Location: admin.php");
    exit();
}

// 统一回复小尾巴
define('REPLY_SUFFIX', "\n\n—— 客服小助手");

// 验证微信服务器
$signature = $_GET["signature"] ?? '';
$timestamp = $_GET["timestamp"] ?? '';
$nonce = $_GET["nonce"] ?? '';
$echostr = $_GET["echostr"] ?? '';
$encrypt_type = $_GET["encrypt_type"] ?? 'raw';

// 1. URL验证处理
if (!empty($echostr)) {
    if (checkSignature()) {
        // 处理加密模式验证
        if ($encrypt_type == 'aes') {
            $crypt = new WXBizMsgCrypt(getValidToken(), WX_ENCODING_AESKEY, WX_APPID);
            $errCode = $crypt->VerifyURL($signature, $timestamp, $nonce, $echostr, $msg);
            if ($errCode == 0) {
                echo $msg;
            }
        } else {
            echo $echostr;
        }
    }
    exit();
}

// 2. 消息处理
$postStr = file_get_contents("php://input");
handleMessage($postStr);

function handleMessage($postStr) {
    // 消息解密处理
    if ($_GET['encrypt_type'] == 'aes') {
        $crypt = new WXBizMsgCrypt(getValidToken(), WX_ENCODING_AESKEY, WX_APPID);
        $errCode = $crypt->decryptMsg(
            $_GET['msg_signature'],
            $_GET['timestamp'],
            $_GET['nonce'],
            $postStr,
            $msg
        );
        if ($errCode != 0) {
            error_log("解密失败: $errCode");
            return;
        }
        $postData = simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
    } else {
        $postData = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
    }

    // 处理消息内容
    if ($postData->MsgType == 'text') {
        $content = queryKeyword((string)$postData->Content);
    } else {
        $content = "暂不支持此类型消息";
    }

    // 添加统一小尾巴
    $content .= REPLY_SUFFIX;

    // 构造响应
    $response = "<xml>
        <ToUserName><![CDATA[{$postData->FromUserName}]]></ToUserName>
        <FromUserName><![CDATA[{$postData->ToUserName}]]></FromUserName>
        <CreateTime>".time()."</CreateTime>
        <MsgType><![CDATA[text]]></MsgType>
        <Content><![CDATA[$content]]></Content>
    </xml>";

    // 加密返回
    if ($_GET['encrypt_type'] == 'aes') {
        $crypt->encryptMsg($response, $_GET['timestamp'], $_GET['nonce'], $encryptMsg);
        echo $encryptMsg;
    } else {
        echo $response;
    }
}

// 增强版签名验证（支持多Token）
function checkSignature() {
    $validTokens = [WX_TOKEN, WX_NEW_TOKEN];
    
    foreach ($validTokens as $token) {
        $tmpArr = [$token, $_GET["timestamp"], $_GET["nonce"]];
        sort($tmpArr, SORT_STRING);
        $tmpStr = sha1(implode($tmpArr));
        if ($tmpStr == $_GET["signature"]) {
            return true;
        }
    }
    return false;
}

// 获取当前有效的Token
function getValidToken() {
    $validTokens = [WX_TOKEN, WX_NEW_TOKEN];
    
    foreach ($validTokens as $token) {
        $tmpArr = [$token, $_GET["timestamp"], $_GET["nonce"]];
        sort($tmpArr, SORT_STRING);
        $tmpStr = sha1(implode($tmpArr));
        if ($tmpStr == $_GET["signature"]) {
            return $token;
        }
    }
    return WX_TOKEN; // 默认返回原Token
}

// 数据库查询（增加缓存优化）
function queryKeyword($keyword) {
    static $conn = null;
    
    if (!$conn) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        mysqli_set_charset($conn, 'utf8mb4');
    }
    
    $stmt = $conn->prepare("SELECT content FROM keywords WHERE keyword = ?");
    $stmt->bind_param("s", $keyword);
    $stmt->execute();
    
    if ($result = $stmt->get_result()) {
        return $result->num_rows > 0
            ? $result->fetch_assoc()['content']
            : "未找到相关内容";
    }
    return "系统繁忙，请稍后再试";
}
