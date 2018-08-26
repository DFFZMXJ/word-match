<?php
/**
 * Get words, verify words and provide another word.
 */
header("Content-Type:application/json");
//Connect to database.
class DB extends SQLite3{
    /**
     * Database uses SQLite.
     */
    function __construct($path){
        return $this->open($path);
    }
    function randWord(){
        //Randomize select a word from database.
        $words = $this->query("select count(*) from 'dictonary';")->fetchArray()[0];
        return $this->query("select * from 'dictonary' limit 1 offset ".mt_rand(0,$words-1)."")->fetchArray()['wholeWord'];
    }
    function verifyWord($raw_word,$enter_word){
        //Verify the word and provide a new word.
        if(!preg_match('/^[\x{4e00}-\x{9fa5}]{4}$/u',$enter_word)) return [
            'ok'=>false,
            'error'=>'成语不合法！'
        ];
        if($this->query("select * from 'dictonary' where wholeWord='".$enter_word."'")->fetchArray()){
            mb_internal_encoding('UTF-8'); 
            if(mb_substr($raw_word,mb_strlen($raw_word,"utf-8")-1,1)!=mb_substr($enter_word,0,1)) return [
                'ok'=>false,
                'error'=>'新成语首字和提交成语最后一字不相同！'
            ];
            $count_of_words = $this->query("select count(*) from 'dictonary' where firstWord='".mb_substr($enter_word,mb_strlen($enter_word,"utf-8")-1,1)."' ;")->fetchArray()[0];
            if($count_of_words<1) return [
                'ok'=>true,
                'new'=>null
            ];
            return [
                'ok'=>ture,
                'new'=>$this->query("select * from 'dictonary' where firstWord='".mb_substr($enter_word,mb_strlen($enter_word,"utf-8")-1,1)."' limit 1 offset ".mt_rand(0,$count_of_words-1))->fetchArray()['wholeWord']
            ];
        }else return [
            'ok'=>false,
            'error'=>'成语不存在！'
        ];
    }
}
$database = new DB('storage.db');
switch(isset($_GET['type'])?strtolower($_GET['type']):'default'){
    case "start":
        echo json_encode([
            'status'=>200,
            'message'=>'成功获取成语！',
            'data'=>[
                'word'=>$database->randWord()
            ]
        ],JSON_PRETTY_PRINT);
        break;
    case "verify":
        if(!isset($_GET['rawword'])||!isset($_GET['newword'])){
            header("HTTP/1.1 406 Not Acceptable");
            echo json_encode([
                'status'=>406,
                'message'=>'你没有提供成语！'
            ],JSON_PRETTY_PRINT);
            break;
        }
        if(($verified = $database->verifyWord($_GET['rawword'],$_GET['newword']))['ok']){
            echo json_encode([
                'status'=>200,
                'message'=>'匹配到有效的成语！',
                'data'=>[
                    'word'=>$verified['new'],
                    'win'=>$verified['new']?false:true
                ]
            ],JSON_PRETTY_PRINT);
        }else {
            header("HTTP/1.1 404 Not Found");
            echo json_encode([
                'status'=>404,
                'message'=>$verified['error']
            ],JSON_PRETTY_PRINT);
        }
        break;
    default:
        header("HTTP/1.1 400 Bad Request");
        echo json_encode([
            'status'=>400,
            'message'=>'不合法的请求！'
        ],JSON_PRETTY_PRINT);
        break;
}
//Disconnect from database.
$database->close();