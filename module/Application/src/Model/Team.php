<?php
namespace Application\Model;

class Team
{
	public $id;
	public $name;
	public $group;

	public function __construct(array $cfg=[]){
		foreach($cfg as $k=>$v) $this->{$k}=!empty($v)?$v:null;
	}

	public function exchangeArray(array $data)
	{
		$this->id = !empty($data['tb1001_id']) ? $data['tb1001_id'] : null;
		$this->name = !empty($data['tb1001_name']) ? $data['tb1001_name'] : null;
		$this->group  = !empty($data['tb1001_group']) ? $data['tb1001_group'] : null;
	}
}