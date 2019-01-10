<?php
namespace Application\Model;

class Match
{
	public $id;
	public $team_a;
	public $team_b;
	public $score_a;
	public $score_b;
	public $type;

	public function __construct(array $cfg=[]){
		foreach($cfg as $k=>$v) $this->{$k}=!empty($v)?$v:null;
	}

	public function exchangeArray(array $data)
	{
		$this->id = !empty($data['tb1003_id']) ? $data['tb1003_id'] : null;
		$this->team_a = !empty($data['tb1003_tb1001_id_a']) ? $data['tb1003_tb1001_id_a'] : null;
		$this->team_b = !empty($data['tb1003_tb1001_id_b']) ? $data['tb1003_tb1001_id_b'] : null;
		$this->score_a = !empty($data['tb1003_score_a']) ? $data['tb1003_score_a'] : null;
		$this->score_b = !empty($data['tb1003_score_b']) ? $data['tb1003_score_b'] : null;
		$this->type = !empty($data['tb1003_type']) ? $data['tb1003_type'] : null;
	}
}