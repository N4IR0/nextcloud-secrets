<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Tobias Knöppler <thecalcaholic@web.de>
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * @copyright Copyright (c) 2023 Your name <your@email.com>
 *
 * @author Your name <your@email.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Secrets\Migration;

use Closure;
use OCP\DB\Exception;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version1001Date20230122142756 extends SimpleMigrationStep {
	/**
	 * Version1001Date20230122142756 constructor.
	 *
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();
		$table = $schema->getTable("secrets");
		if ($table->hasColumn("iv_str")) {
			return null;
		}
		$table->addColumn("iv_str", Types::TEXT, ['notnull' => false, 'length' => null, 'default' => '']);
		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @throws Exception
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$qb = $this->connection->getQueryBuilder();
		$results = $qb->select('id', 'iv')
			->from('secrets')
			->executeQuery();
		$secret = $results->fetch();
		while ($secret) {
			$qb = $this->connection->getQueryBuilder();
			$qb->update("secrets")
				->where($qb->expr()->eq('id', $qb->createNamedParameter($secret['id'])))
				->set('iv_str', $qb->createNamedParameter(self::fixSerialization($secret['iv'])));
			$qb->executeStatement();
		}
	}

	public static function fixSerialization($utf8Data): ?string {
		if ($utf8Data == null) {
			return null;
		}

		if (is_resource($utf8Data)) {
			$utf8Str = stream_get_contents($utf8Data);
			fclose($utf8Data);
		} else {
			$utf8Str = $utf8Data;
		}

		return base64_encode(utf8_decode($utf8Str));
	}
}
