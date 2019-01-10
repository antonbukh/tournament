<?php
namespace Application\Model;

use RuntimeException;
use Zend\Db\TableGateway\TableGatewayInterface;

class TeamTable
{
	public $tableGateway;
	
	public function __construct(TableGatewayInterface $tableGateway)
	{
		$this->tableGateway = $tableGateway;
	}

	public function getAll()
	{
		return iterator_to_array($this->tableGateway->select());
	}

	public function get($id)
	{
		$id = (int) $id;
		$rowset = $this->tableGateway->select(['id' => $id]);
		$row = $rowset->current();

		return $row;
	}

	public function save(Team $Team)
	{
		$data = [
			'tb1001_id' => $Team->id
			,'tb1001_name'  => $Team->name
			,'tb1001_group'  => $Team->group
		];

		$id = (int) $Team->id;

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