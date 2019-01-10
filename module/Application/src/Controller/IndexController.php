<?php
/**
 * @link	  http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
	private $tableTeam;
	private $tableMatch;
	private $tableGateway;

	public function __construct(
		\Application\Model\TeamTable $tableTeam
		,\Application\Model\MatchTable $tableMatch
	)
	{
		$this->tableTeam = $tableTeam;
		$this->tableMatch = $tableMatch;
		$this->tableGateway=$this->tableTeam->tableGateway;
	}

	/**
	 * Generate all the possible combinations among a set of nested arrays.
	 *
	 * @param array $data  The entrypoint array container.
	 * @param array $all   The TournamentFinal container (used internally).
	 * @param array $group The sub container (used internally).
	 * @param mixed $val   The value to append (used internally).
	 * @param int   $i   The key index (used internally).
	 */
	function combinations(array $data, array &$all = array(), array $group = array(), $value = null, $i = 0)
	{
		$keys = array_keys($data);
		if (isset($value) === true) {
			array_push($group, $value);
		}

		if ($i >= count($data)) {
			array_push($all, $group);
		} else {
			$currentKey  = $keys[$i];
			$currentElement = $data[$currentKey];
			foreach ($currentElement as $val) {
				$this->combinations($data, $all, $group, $val, $i + 1);
			}
		}

		return $all;
	}

	// This is the user-defined function used to compare 
	// values to sort the input array 
	function comparatorFunc($x, $y) 
	{		
		// If $x is equal to $y it returns 0 
		if ($x->score_a==$y->score_a) 
			return 0; 
		// if x is less than y then it returns -1 
		// else it returns 1     
		if ($x->score_a<$y->score_a) 
			return -1; 
		else
			return 1; 
	} 

	public function truncateAction()
	{
		$a=[
			"tb1001_team"
			,"tb1003_match"
		];
		foreach($a as $b) $this->tableGateway->getAdapter()->driver->getConnection()->execute("TRUNCATE TABLE $b");
		return $this->redirect()->toRoute('application', ['action'=>'index']);
	}

	public function teamsAction()
	{
		$this->tableTeam->delete(["1"=>1]);
		$this->createTeams();
		return $this->redirect()->toRoute('application', ['action'=>'index']);
	}

	public function matchesAction()
	{
		$teams=$this->tableTeam->getAll();
		$this->createMatches();
		return $this->redirect()->toRoute('application', ['action'=>'index']);
	}

	public function playoffAction()
	{
		$teams=$this->tableTeam->getAll();
		$matches=$this->tableMatch->getAll("MATCH");
		$this->createPrePlayOff();
		$this->createPlayOff();
		return $this->redirect()->toRoute('application', ['action'=>'index']);
	}

	public function finalAction()
	{
		$teams=$this->tableTeam->getAll();
		$matches=$this->tableMatch->getAll("PLAYOFF");
		$this->createFinal();
		return $this->redirect()->toRoute('application', ['action'=>'index']);
	}

	public function indexAction()
	{
		$teams=$this->tableTeam->getAll();
		$matches=$this->tableMatch->getAll("MATCH");
		$prePlayOff=$this->tableMatch->getAll("PREPLAYOFF");
		$playOff=$this->tableMatch->getAll("PLAYOFF");
		$final=$this->tableMatch->getAll("FINAL");
		$winner=$this->tableMatch->getAll("FINALRES");

		return new ViewModel([
			'controller'=>$this->getEvent()->getRouteMatch()->getParam('controller')
			,'action'=>$this->getEvent()->getRouteMatch()->getParam('action')
			,'teams' => $teams
			,'matches' => $matches
			,'prePlayOff' => $prePlayOff
			,'playOff' => $playOff
			,'final' => $final
			,'winner' => $winner
		]);
	}

	protected function createTeams()
	{
		$teams=[];
		for($i=1;$i<=16;$i++)
			$teams[]="Team-".$i;
		
		shuffle($teams);
		
		for($i=0;$i<count($teams);$i++)
		{
			$team=new \Application\Model\Team(["name"=>$teams[$i],"group"=>$i<8?"A":"B"]);
			$this->tableTeam->save($team);
		}
	}

	protected function createMatches()
	{
		$teamA=[];
		$teamB=[];
		$matches=[];
		$teams=$this->tableTeam->getAll();
		foreach($teams as $r)
		{
			if($r->group=="A")$teamA[]=$r->id;
			else $teamB[]=$r->id;
		}

		for ($i=0; $i < count($teamA)-1; $i++) { 
			$teamA1 = array_slice($teamA, $i, 1);
			$teamA2 = array_slice($teamA, $i+1);
			$matches[]=$this->combinations(["A"=>$teamA1,"B"=>$teamA2]);
		}

		for ($i=0; $i < count($teamB)-1; $i++) { 
			$teamB1 = array_slice($teamB, $i, 1);
			$teamB2 = array_slice($teamB, $i+1);
			$matches[]=$this->combinations(["A"=>$teamB1,"B"=>$teamB2]);
		}

		$this->saveMatch($matches,"MATCH");
	}

	protected function saveMatch($matches,$type)
	{
		for($i=0; $i < count($matches); $i++){
			foreach($matches[$i] as $r){
				$data=new \Application\Model\Match(["team_a"=>$r[0],"team_b"=>$r[1],"score_a"=>rand(1,10),"score_b"=>rand(1,10),"type"=>$type]);
				$this->tableMatch->save($data);
			}
		}
	}

	protected function createPrePlayOff()
	{
		$groups=["A","B"];

		$this->tableMatch->delete(["tb1003_type"=>"MATCHRES"]);
		$this->tableMatch->delete(["tb1003_type"=>"PREPLAYOFF"]);

		foreach($groups as $g){
			$sql="INSERT INTO tb1003_match (tb1003_tb1001_id_a,tb1003_score_a,tb1003_type)
				SELECT tb1001_id,score,type
				FROM (
					SELECT 
					tb1001_id
					,(
					SELECT SUM(IF(tb1001_id=tb1003_tb1001_id_a,tb1003_score_a,tb1003_score_b)) 
					FROM tb1003_match 
					WHERE tb1001_id IN (tb1003_tb1001_id_a,tb1003_tb1001_id_b) AND tb1003_type='MATCH'
					) score 
					,'MATCHRES' type
					FROM tb1001_team
					WHERE tb1001_group='$g'
				) T
				ORDER BY score DESC
			";
			$this->tableGateway->getAdapter()->driver->getConnection()->execute($sql);
		}
		$teams=$this->tableMatch->getAll("MATCHRES");

		usort($teams, [$this,"comparatorFunc"]); // array sort function of score element by asc

		$bestworst=[];
		$slice = floor(count($teams)/2);
		for($i=0;$i<$slice;$i++) $bestworst[]=[$teams[$i]->team_a,$teams[count($teams)-1-$i]->team_a]; // after sorting take first and last

		foreach($bestworst as $r)
		{
			$model=new \Application\Model\Match(["team_a"=>$r[0],"team_b"=>$r[1],"score_a"=>rand(1,10),"score_b"=>rand(1,10),"type"=>"PREPLAYOFF"]);
			$this->tableMatch->save($model);
		}
	}

	protected function createPlayOff()
	{
		$groups=["A","B"];

		$this->tableMatch->delete(["tb1003_type"=>"PREPLAYOFFRES"]);
		$this->tableMatch->delete(["tb1003_type"=>"PLAYOFF"]);

		// foreach($groups as $g){
			$sql="INSERT INTO tb1003_match (tb1003_tb1001_id_a,tb1003_score_a,tb1003_type)
				SELECT tb1001_id,score,type
				FROM (
					SELECT 
					tb1001_id
					,(
					SELECT SUM(IF(tb1001_id=tb1003_tb1001_id_a,tb1003_score_a,tb1003_score_b)) 
					FROM tb1003_match 
					WHERE tb1001_id IN (tb1003_tb1001_id_a,tb1003_tb1001_id_b) AND tb1003_type='PREPLAYOFF'
					) score 
					,'PREPLAYOFFRES' type
					FROM tb1001_team
				) T
				ORDER BY score DESC
				LIMIT 4
			";
					// WHERE tb1001_group='$g'
			$this->tableGateway->getAdapter()->driver->getConnection()->execute($sql);
		// }
		$teams=$this->tableMatch->getAll("PREPLAYOFFRES");

		usort($teams, [$this,"comparatorFunc"]); // array sort function of score element by asc

		$bestworst=[];
		$slice = floor(count($teams)/2);
		for($i=0;$i<$slice;$i++) $bestworst[]=[$teams[$i]->team_a,$teams[count($teams)-1-$i]->team_a]; // after sorting take first and last

		foreach($bestworst as $r)
		{
			$model=new \Application\Model\Match(["team_a"=>$r[0],"team_b"=>$r[1],"score_a"=>rand(1,10),"score_b"=>rand(1,10),"type"=>"PLAYOFF"]);
			$this->tableMatch->save($model);
		}
	}

	protected function createFinal()
	{
		$groups=["A","B"];

		$this->tableMatch->delete(["tb1003_type"=>"PLAYOFFRES"]);
		$this->tableMatch->delete(["tb1003_type"=>"FINAL"]);
		$this->tableMatch->delete(["tb1003_type"=>"FINALRES"]);

		// foreach($groups as $g){
			$sql="INSERT INTO tb1003_match (tb1003_tb1001_id_a,tb1003_score_a,tb1003_type)
				SELECT tb1001_id,score,type
				FROM (
					SELECT 
					tb1001_id
					,(
					SELECT SUM(IF(tb1001_id=tb1003_tb1001_id_a,tb1003_score_a,tb1003_score_b)) 
					FROM tb1003_match 
					WHERE tb1001_id IN (tb1003_tb1001_id_a,tb1003_tb1001_id_b) AND tb1003_type='PLAYOFF'
					) score 
					,'PLAYOFFRES' type
					FROM tb1001_team
				) T
				ORDER BY score DESC
				LIMIT 2
			";
					// WHERE tb1001_group='$g'
			$this->tableGateway->getAdapter()->driver->getConnection()->execute($sql);
		// }
		$teams=$this->tableMatch->getAll("PLAYOFFRES");

		// usort($teams, [$this,"comparatorFunc"]); // array sort function of score element by asc

		$bestworst=[];
		$slice = floor(count($teams)/2);
		for($i=0;$i<$slice;$i++) $bestworst[]=[$teams[$i]->team_a,$teams[count($teams)-1-$i]->team_a]; // after sorting take first and last

		foreach($bestworst as $r)
		{
			$model=new \Application\Model\Match(["team_a"=>$r[0],"team_b"=>$r[1],"score_a"=>rand(1,10),"score_b"=>rand(1,10),"type"=>"FINAL"]);
			$this->tableMatch->save($model);
		}

		$sql="INSERT INTO tb1003_match (tb1003_tb1001_id_a,tb1003_score_a,tb1003_type)
			SELECT tb1001_id,score,type
			FROM (
				SELECT 
				tb1001_id
				,(
				SELECT SUM(IF(tb1001_id=tb1003_tb1001_id_a,tb1003_score_a,tb1003_score_b)) 
				FROM tb1003_match 
				WHERE tb1001_id IN (tb1003_tb1001_id_a,tb1003_tb1001_id_b) AND tb1003_type='FINAL'
				) score 
				,'FINALRES' type
				FROM tb1001_team
			) T
			ORDER BY score DESC
			LIMIT 1
		";
			$this->tableGateway->getAdapter()->driver->getConnection()->execute($sql);
	}
}
