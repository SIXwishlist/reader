<?php

	class article_view_unit extends unit {

		private $article_id = NULL;
		private $article_source_id = NULL;
		private $article_published = NULL;
		private $article_html = NULL;
		private $article_read = NULL;

		public function setup($config = array()) {

			//--------------------------------------------------
			// Config

				$config = array_merge(array(
						'source' => NULL,
						'article' => NULL,
						'read' => NULL,
					), $config);

			//--------------------------------------------------
			// Source

				$db = db_get();

				$sql = 'SELECT
							s.id,
							s.title
						FROM
							' . DB_PREFIX . 'source AS s
						WHERE
							s.ref = "' . $db->escape($config['source']) . '" AND
							s.deleted = "0000-00-00 00:00:00"';

				if ($row = $db->fetch_row($sql)) {
					$source_id = $row['id'];
					$source_title = $row['title'];
					$source_ref = $config['source'];
				} else {
					error_send('page-not-found');
				}

			//--------------------------------------------------
			// Article

				$sql = 'SELECT
							sa.id,
							sa.title,
							sa.link,
							sa.published,
							sa.description,
							IF(sar.article_id IS NOT NULL, 1, 0) AS article_read
						FROM
							' . DB_PREFIX . 'source_article AS sa
						LEFT JOIN
							' . DB_PREFIX . 'source_article_read AS sar ON sar.article_id = sa.id AND sar.user_id = "' . $db->escape(USER_ID) . '"
						WHERE
							sa.id = "' . $db->escape($config['article']) . '" AND
							sa.source_id = "' . $db->escape($source_id) . '"
						GROUP BY
							sa.id';

				if ($row = $db->fetch_row($sql)) {

					$article_id = $row['id'];
					$article_title = $row['title'];
					$article_link = $row['link'];
					$article_published = $row['published'];
					$article_html = $row['description'];
					$article_read = ($row['article_read'] == 1);

					$article_url = gateway_url('article', array('id' => $article_id));

				} else {

					exit_with_error('Cannot find article "' . $config['article'] . '"');

				}

			//--------------------------------------------------
			// Article read

				if (($config['read'] === true) || ($config['read'] === NULL && $article_read === false)) {

					if (!$article_read) {

						$values = array(
								'article_id' => $article_id,
								'user_id' => USER_ID,
								'read_date' => date('Y-m-d H:i:s'),
							);

						$db->insert(DB_PREFIX . 'source_article_read', $values, $values);

					}

					$article_read = true;

				} else if ($config['read'] === false) {

					if ($article_read) {

						$db->query('DELETE FROM
										' . DB_PREFIX . 'source_article_read
									WHERE
										article_id = "' . $db->escape($article_id) . '" AND
										user_id = "' . $db->escape(USER_ID) . '"');

					}

					$article_read = false;

				}

			//--------------------------------------------------
			// Variables

				$this->article_id = $article_id;
				$this->article_source_id = $source_id;
				$this->article_published = $article_published;
				$this->article_html = $article_html;
				$this->article_read = $article_read;

				$this->set('source_title', $source_title);
				$this->set('article_title', $article_title);
				$this->set('article_url', $article_url);

		}

		public function html_get() {

			// TODO: Return "clean" HTML

		}

		public function read_get() {
			return $this->article_read;
		}

		public function sibling_id_get($rel) {

			$db = db_get();

			$where_sql = '
				sa.source_id = "' . $db->escape($this->article_source_id) . '"';

			if ($rel > 0) {

				$where_sql .= ' AND
					(
						sa.published > "' . $db->escape($this->article_published) . '" OR
						(
							sa.published = "' . $db->escape($this->article_published) . '" AND
							sa.id > "' . $db->escape($this->article_id) . '"
						)
					)';

					// [2013-09-13 07:43:34] ... id=5650 ... (2013-09-11 15:54:32)
					// [XXXX-XX-XX XX:XX:XX] ... id=5649 ... (2013-09-11 15:54:32)
					// [2013-09-13 07:43:36] ... id=5648 ... (2013-09-11 15:54:34)
					// [2013-09-13 07:43:37] ... id=5647 ... (2013-09-11 15:54:36)
					//
					// Published date the same for 5650 and 5649, the sub condition
					// for matching published dates is correct (sa.id > X), but the
					// order was wrong, 5649 should have been seen first (was not
					// specified at the time).

				$order_sql = '
					sa.published ASC,
					sa.id ASC';

			} else {

				$where_sql .= ' AND
					(
						sa.published < "' . $db->escape($this->article_published) . '" OR
						(
							sa.published = "' . $db->escape($this->article_published) . '" AND
							sa.id < "' . $db->escape($this->article_id) . '"
						)
					)';

				$order_sql = '
					sa.published DESC,
					sa.id DESC';

			}

			$sql = 'SELECT
						sa.id
					FROM
						' . DB_PREFIX . 'source_article AS sa
					WHERE
						' . $where_sql . '
					ORDER BY
						' . $order_sql .'
					LIMIT
						1';

			if ($row = $db->fetch_row($sql)) {
				return $row['id'];
			} else {
				return NULL;
			}

		}

	}

?>
