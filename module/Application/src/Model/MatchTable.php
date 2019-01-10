<?php
namespace Application\Model;

use RuntimeException;
use Zend\Db\TableGateway\TableGatewayInterface;

class MatchTable
{
	public $tableGateway;
	
	public function __construct(TableGatewayInterface $tableGateway)
	{
		$this->tableGateway = $tableGateway;
	}

	public function getAll($type)
	{
		return iterator_to_array($this->tableGateway->select(['tb1003_type' => $type]));
	}

	public function get($id)
	{
		$id = (int) $id;
		$rowset = $this->tableGateway->select(['id' => $id]);
		$row = $rowset->current();
		return $row;
	}

	public function save(Match $m)
	{
		$data = [
			'tb1003_id' => $m->id
			,'tb1003_tb1001_id_a'  => $m->team_a
			,'tb1003_tb1001_id_b'  => $m->team_b
			,'tb1003_score_a'  => $m->score_a
			,'tb1003_score_b'  => $m->score_b
			,'tb1003_type'  => $m->type
		];

		$id = (int) $m->id;

		if ($id === 0) {
			$this->tableGateway->insert($data);
			return;
		}

		$this->tableGateway->update($data, ['id' => $id]);
	}

	public function delete($where)
	{
		$this->tableGateway->delete($where);
	}
}