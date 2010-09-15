<?php

	/**
	 * Issues <-> Files table
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 2.0
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage tables
	 */

	/**
	 * Issues <-> Files table
	 *
	 * @package thebuggenie
	 * @subpackage tables
	 */
	class TBGIssueFilesTable extends B2DBTable
	{

		const B2DBNAME = 'issuefiles';
		const ID = 'issuefiles.id';
		const SCOPE = 'issuefiles.scope';
		const UID = 'issuefiles.uid';
		const ATTACHED_AT = 'issuefiles.attached_at';
		const FILE_ID = 'issuefiles.file_id';
		const ISSUE_ID = 'issuefiles.issue_id';

		public function __construct()
		{
			parent::__construct(self::B2DBNAME, self::ID);
			parent::_addForeignKeyColumn(self::UID, B2DB::getTable('TBGUsersTable'), TBGUsersTable::ID);
			parent::_addForeignKeyColumn(self::SCOPE, TBGScopesTable::getTable(), TBGScopesTable::ID);
			parent::_addForeignKeyColumn(self::ISSUE_ID, TBGIssuesTable::getTable(), TBGIssuesTable::ID);
			parent::_addForeignKeyColumn(self::FILE_ID, B2DB::getTable('TBGFilesTable'), TBGFilesTable::ID);
			parent::_addInteger(self::ATTACHED_AT, 10);
		}

		public function addFileToIssue($issue_id, $file_id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::ISSUE_ID, $issue_id);
			$crit->addWhere(self::FILE_ID, $file_id);
			$crit->addWhere(self::SCOPE, TBGContext::getScope()->getID());
			if ($this->doCount($crit) == 0)
			{
				$crit = $this->getCriteria();
				$crit->addInsert(self::SCOPE, TBGContext::getScope()->getID());
				$crit->addInsert(self::ATTACHED_AT, time());
				$crit->addInsert(self::ISSUE_ID, $issue_id);
				$crit->addInsert(self::FILE_ID, $file_id);
				$this->doInsert($crit);
			}
		}

		public function removeFileFromIssue($issue_id, $file_id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::ISSUE_ID, $issue_id);
			$crit->addWhere(self::FILE_ID, $file_id);
			$crit->addWhere(self::SCOPE, TBGContext::getScope()->getID());
			$this->doDelete($crit);

			$crit = $this->getCriteria();
			$crit->addWhere(self::FILE_ID, $file_id);
			$crit->addWhere(self::SCOPE, TBGContext::getScope()->getID());
			if ($this->doCount($crit) == 0)
			{
				$file = B2DB::getTable('TBGFilesTable')->doSelectById($file_id);
				unlink(TBGSettings::getUploadsLocalpath().$file->get(TBGFilesTable::REAL_FILENAME));
				B2DB::getTable('TBGFilesTable')->doDeleteById($file_id);
			}
		}
		
		public function getByIssueID($issue_id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::ISSUE_ID, $issue_id);
			$res = $this->doSelect($crit);
			
			$ret_arr = array();

			if ($res)
			{
				while ($row = $res->getNextRow())
				{
					$ret_arr[$row->get(TBGFilesTable::ID)] = array('filename' => $row->get(TBGFilesTable::ORIGINAL_FILENAME), 'description' => $row->get(TBGFilesTable::DESCRIPTION), 'timestamp' => $row->get(TBGFilesTable::UPLOADED_AT));
				}
			}
			
			return $ret_arr;
		}

		public function removeByIssueIDandFileID($issue_id, $file_id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::ISSUE_ID, $issue_id);
			if ($res = $this->doSelectById($file_id, $crit))
			{
				$this->doDelete($crit);
			}
			return $res;
		}
		
	}
