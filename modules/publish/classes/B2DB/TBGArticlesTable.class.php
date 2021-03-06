<?php

	class TBGArticlesTable extends TBGB2DBTable 
	{

		const B2DB_TABLE_VERSION = 1;
		const B2DBNAME = 'articles';
		const ID = 'articles.id';
		const NAME = 'articles.name';
		const CONTENT = 'articles.content';
		const IS_PUBLISHED = 'articles.is_published';
		const DATE = 'articles.date';
		const AUTHOR = 'articles.author';
		const SCOPE = 'articles.scope';
		
		/**
		 * Return an instance of this table
		 *
		 * @return TBGArticlesTable
		 */
		public static function getTable()
		{
			return B2DB::getTable('TBGArticlesTable');
		}

		public function __construct()
		{
			parent::__construct(self::B2DBNAME, self::ID);
			parent::_addVarchar(self::NAME, 255);
			parent::_addText(self::CONTENT, false);
			parent::_addBoolean(self::IS_PUBLISHED);
			parent::_addInteger(self::DATE, 10);
			parent::_addForeignKeyColumn(self::AUTHOR, TBGUsersTable::getTable(), TBGUsersTable::ID);
			parent::_addForeignKeyColumn(self::SCOPE, TBGScopesTable::getTable(), TBGScopesTable::ID);
		}

		public function getAllArticles()
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::SCOPE, TBGContext::getScope()->getID());
			$crit->addOrderBy(self::NAME);

			$res = $this->doSelect($crit);
			$articles = array();

			if ($res)
			{
				while ($row = $res->getNextRow())
				{
					$a_id = $row->get(self::ID);
					$articles[$a_id] = PublishFactory::article($a_id, $row);
				}
			}

			return $articles;
		}

		public function getArticleByName($name)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::NAME, $name);
			$crit->addWhere(self::SCOPE, TBGContext::getScope()->getID());
			$row = $this->doSelectOne($crit, 'none');

			return $row;
		}

		public function doesArticleExist($name)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::NAME, $name);
			$crit->addWhere(self::SCOPE, TBGContext::getScope()->getID());

			return (bool) $this->doCount($crit);
		}

		public function deleteArticleByName($name)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::NAME, $name);
			$crit->addWhere(self::SCOPE, TBGContext::getScope()->getID());
			$crit->setLimit(1);
			$row = $this->doDelete($crit);

			return $row;
		}

		public function getArticleByID($article_id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::SCOPE, TBGContext::getScope()->getID());
			$row = $this->doSelectByID($article_id, $crit);

			return $row;
		}

		public function getUnpublishedArticlesByUser($user_id)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::IS_PUBLISHED, false);
			$crit->addWhere(self::SCOPE, TBGContext::getScope()->getID());

			$res = $this->doSelect($crit);

			return $res;
		}

		public function doesNameConflictExist($name, $id, $scope = null)
		{
			$scope = ($scope === null) ? TBGContext::getScope()->getID() : $scope;

			$crit = $this->getCriteria();
			$crit->addWhere(self::NAME, $name);
			$crit->addWhere(self::ID, $id, B2DBCriteria::DB_NOT_EQUALS);
			$crit->addWhere(self::SCOPE, $scope);

			return (bool) ($res = $this->doSelect($crit));
		}
		
		public function findArticlesLikeName($name, $project, $limit = 5, $offset = 0)
		{
			$crit = $this->getCriteria();
			if ($project instanceof TBGProject)
			{
				$ctn = $crit->returnCriterion(self::NAME, "%{$name}%", B2DBCriteria::DB_LIKE);
				$ctn->addWhere(self::NAME, "category:" . $project->getKey() . "%", B2DBCriteria::DB_LIKE);
				$crit->addWhere($ctn);
				
				$ctn = $crit->returnCriterion(self::NAME, "%{$name}%", B2DBCriteria::DB_LIKE);
				$ctn->addWhere(self::NAME, $project->getKey() . "%", B2DBCriteria::DB_LIKE);
				$crit->addOr($ctn);
			}
			else
			{
				$crit->addWhere(self::NAME, "%{$name}%", B2DBCriteria::DB_LIKE);
			}
			
			$resultcount = $this->doCount($crit);
			
			if ($resultcount)
			{
				$crit->setLimit($limit);

				if ($offset)
					$crit->setOffset($offset);

				return array($resultcount, $this->doSelect($crit));
			}
			else
			{
				return array($resultcount, array());
			}
		}

		public function save($name, $content, $published, $author, $id = null, $scope = null)
		{
			$scope = ($scope !== null) ? $scope : TBGContext::getScope()->getID();
			$crit = $this->getCriteria();
			if ($id == null)
			{
				$crit->addInsert(self::NAME, $name);
				$crit->addInsert(self::CONTENT, $content);
				$crit->addInsert(self::IS_PUBLISHED, (bool) $published);
				$crit->addInsert(self::AUTHOR, $author);
				$crit->addInsert(self::DATE, time());
				$crit->addInsert(self::SCOPE, $scope);
				$res = $this->doInsert($crit);
				return $res->getInsertID();
			}
			else
			{
				$crit->addUpdate(self::NAME, $name);
				$crit->addUpdate(self::CONTENT, $content);
				$crit->addUpdate(self::IS_PUBLISHED, (bool) $published);
				$crit->addUpdate(self::AUTHOR, $author);
				$crit->addUpdate(self::DATE, time());
				$res = $this->doUpdateById($crit, $id);
				return $res;
			}
		}

	}

