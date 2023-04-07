<?php


namespace Dxkjcomposer\Commsgapi;


class Result
{
    public $status;
    public $msg;
    public $data;
    public $code;

    public function __construct(bool $status,string $msg='',$data=[],string $code='')
    {
        $this->status=$status;
        $this->msg=$msg!='' ? $msg : ($status ? 'success' : 'failed');
        $this->data=$data;
        $this->code=$code;
    }

    /**
     * 转换成arr
     * @return array
     */
    public function toArr():array
    {
        return (array)$this;
    }
}