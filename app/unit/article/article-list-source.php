<?php

	class article_list_source_unit extends unit {

		protected $config = array(
				'view_url' => array('type' => 'url'),
				'source'   => NULL,
				'state'    => 'unread',
			);

		protected function authenticate($config) {
			return (USER_LOGGED_IN === true);
		}

		protected function setup($config) {

			//--------------------------------------------------
			// Source

				$db = db_get();

				$sql = 'SELECT
							s.id,
							s.title
						FROM
							' . DB_PREFIX . 'source AS s
						WHERE
							s.ref = ? AND
							s.deleted = "0000-00-00 00:00:00"';

				$parameters = array();
				$parameters[] = array('s', $config['source']);

				if ($row = $db->fetch_row($sql, $parameters)) {
					$source_id = $row['id'];
					$source_title = $row['title'];
					$source_ref = $config['source'];
				} else {
					error_send('page-not-found');
				}

			//--------------------------------------------------
			// Articles

				$parameters = array();
				$parameters[] = array('i', USER_ID);

				$where_sql = '
					sa.source_id = ? AND
					sa.created < ?';

				$parameters[] = array('i', $source_id);
				$parameters[] = array('s', USER_DELAY);

				if ($config['state'] === 'read') {

					$where_sql .= ' AND
						sar.article_id IS NOT NULL';

				} else if ($config['state'] === 'unread') {

					$where_sql .= ' AND
						sar.article_id IS NULL';

				} else if ($config['state'] !== 'all') {

					exit_with_error('Unknown article state "' . $config['state'] . '"');

				}

				$articles = array();

				$sql = 'SELECT
							sa.id,
							sa.title,
							sar.article_id
						FROM
							' . DB_PREFIX . 'source_article AS sa
						LEFT JOIN
							' . DB_PREFIX . 'source_article_read AS sar ON sar.article_id = sa.id AND sar.user_id = ?
						WHERE
							' . $where_sql . '
						GROUP BY
							sa.id
						ORDER BY
							sa.published ' . ($config['state'] === 'unread' ? 'ASC' : 'DESC') . ',
							sa.id ' . ($config['state'] === 'unread' ? 'ASC' : 'DESC') . '
						LIMIT
							50';

				foreach ($db->fetch_all($sql, $parameters) as $row) {

					$articles[] = array(
							'url' => $config['view_url']->get(array('source' => $source_ref, 'id' => $row['id'], 'state' => ($config['state'] == 'unread' ? NULL : $config['state']))),
							'title' => $row['title'],
							'new' => ($config['state'] === 'all' && $row['article_id'] == 0),
						);

				}

			//--------------------------------------------------
			// Variables

				$this->set('source_id', $source_id);
				$this->set('source_title', $source_title);
				$this->set('articles', $articles);

		}

	}

?>