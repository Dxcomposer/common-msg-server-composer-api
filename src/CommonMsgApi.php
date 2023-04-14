<?php

namespace Dxkjcomposer\Commsgapi;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class CommonMsgApi
{
    public array $ips = [];

    public string $projectKey = '';
    public string $token = '';
    public string $requestCode = '';

    /**
     * CommonAuthAPi constructor.
     * @param string $projectKey 项目令牌
     * @param array $ips 消息中心的url地址
     * @param string $token
     * @throws \Exception
     */
    public function __construct(string $projectKey, array $ips,string $token='')
    {
        if(strlen($projectKey)!=20)
        {
            throw new \Exception('$projectKey 长度不合法');
        }
        $this->projectKey=$projectKey;
        $this->ips=$ips;
        $this->token=$token;

        if(class_exists(\Hyperf\Context\Context::class))
        {
            $this->requestCode=\Hyperf\Context\Context::get('request-code','');
        }
    }

    /**
     * 服务端推送
     * @param string $sendUsername 发送方Im账号
     * @param array $to 收信方Im账号
     * @param array $msgBody 消息体 至少包含消息内容 ['content'=>'消息体']
     * @param string $customType 项目自定义消息类型
     * @param string $msgType IM消息类型
     *  const IM_MSG_SYS='M0000';   // 系统消息 需要提示切写入消息记录
     * const IM_MSG_PERSON='M0001'; // 私聊消息 需要提示切写入消息记录
     * const IM_MSG_GROUP='M0002'; // 群聊消息 需要提示切写入消息记录
     * const IM_MSG_OPTION='M0003'; // 前后端操作通知 静默操作
     * const IM_MSG_HEARTBEAT='M9999'; // 前端心跳消息通知
     * @param array $customData 消息自定义参数
     * @param string $source 发送来源
     * @param string $sendUsrPwd 发送方Im账号密码 不传使用默认
     * @return Result
     * @throws GuzzleException
     */
    public  function push(string $sendUsername,array $to,array $msgBody,string $customType='',array $customData=[],string $msgType='M0000',string $source='sys',string $sendUsrPwd=''):Result
    {
        $params=[
            'to'=>array_unique($to),
            'projectKey'=>$this->projectKey,
            'sendUsrName'=>$sendUsername,
            'msgType'=>$msgType,
            'customType'=>$customType,
            'source'=>$source,
            'msgBody'=>$msgBody,
            'customData'=>$customData,
            'sendUsrPwd'=>$sendUsrPwd?$sendUsrPwd:'',
        ];
        return $this->http($this->ips,'/wsClient/push',$params,$this->requestCode,$this->token);
    }

    /**
     * 消息列表
     * @param int $lastId 上一次最大的id
     * @param string $friendUsername 好友账号
     * @param array $customType 项目自定义消息类型
     * @param array $msgType IM消息类型
     * @param int $read 0未读 1已读
     * @param int $pageSize 每页数量
     * @param array $other
     * @return Result
     * @throws GuzzleException
     */
    public  function page(int $lastId, string $friendUsername='', array $customType = [], array $msgType = [], int $read = -1, int $pageSize = 10,array $other=[]):Result
    {
        $params=[
            'lastId'=>$lastId,
        ];
        if($friendUsername)
        {
            $params['friendUsername']=$friendUsername;
        }
        if($customType)
        {
            $params['customType']=$customType;
        }
        if($msgType)
        {
            $params['msgType']=$msgType;
        }
        if($read!=-1)
        {
            $params['read']=$read;
        }
        if($pageSize)
        {
            $params['pageSize']=$pageSize;
        }

        if($other)
        {
            $params=array_merge($params,$other);
        }

        return $this->http($this->ips,'/im-msg/page',$params,$this->requestCode,$this->token);
    }

    /**
     * 设置已读
     * @param array $msgIds 不传代表所有设置已读
     * @return Result
     * @throws GuzzleException
     */
    public function setRead(array $msgIds=[])
    {
        $params=[];
        if($msgIds)
        {
            $params['msgIds']=$msgIds;
        }
        return $this->http($this->ips,'/im-msg/setRead',$params,$this->requestCode,$this->token);
    }

    /**
     * 消息详情 （我发的和我接收的）
     * @param int $msgId
     * @return Result
     * @throws GuzzleException
     */
    public function detail(int $msgId)
    {
        return $this->http($this->ips,'/im-msg/details',['msgId'=>$msgId],$this->requestCode,$this->token);
    }

    /**
     * 联系人
     * @return Result
     * @throws GuzzleException
     */
    public function contactAll()
    {
        return $this->http($this->ips,'/im-contact/list',[],$this->requestCode,$this->token);
    }

    /**
     * 联系人和消息统计
     * @return Result
     * @throws GuzzleException
     */
    public function contactMsgStatistics()
    {
        return $this->http($this->ips,'/im-contact/msgStatistics',[],$this->requestCode,$this->token);
    }


    /**
     * 发送http
     * @param array $ips
     * @param string $uri
     * @param array $params
     * @param string $requestCode
     * @param string $token
     * @return Result
     * @throws GuzzleException
     */
    public static  function http(array $ips,string $uri,array $params=[],string $requestCode='',string $token=''):Result
    {
        try {
            $ip=$ips[array_rand($ips)];
            if(strpos($ip,'http')===false)
            {
                $ip='http://'.$ip;
            }
            $response=(new Client())->post($ip.$uri,['form_params'=>$params,'headers'=>['token'=>$token,'request-code'=>$requestCode]]);

            $res=$response->getBody()->getContents();

            if(!is_string($res))
            {
                return new Result(false,'返回值数据类型错误');
            }
            $res=json_decode($res,true);
            if(!is_array($res))
            {
                return new Result(false,'返回值数据类型错误');
            }
            if(!isset($res['code'])||!isset($res['data'])||!isset($res['msg']))
            {
                return new Result(false,'api结果不含 code data msg');
            }
            return new Result($res['code']==='00000',$res['msg'],$res['data'],$res['code']);
        }catch (\Exception $e)
        {
            return new Result(false,$e->getMessage());
        }
    }
}