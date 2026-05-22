<?php                                                  
	#	@@@@@@@  @@@  @@@  @@@@@@@  @@@@@@@@ @@@  @@@@@@ @@@  @@@ 
	#	@@!  @@@ @@!  @@@ !@@       @@!      @@! !@@     @@!  @@@ 
	#	@!@!@!@  @!@  !@! !@! @!@!@ @!!!:!   !!@  !@@!!  @!@!@!@! 
	#	!!:  !!! !!:  !!! :!!   !!: !!:      !!:     !:! !!:  !!! 
	#	:: : ::   :.:: :   :: :: :   :       :   ::.: :   :   : : 						
	#		 ______  ______   ______   _________   ______  _   _   _   ______   ______   _    __ 
	#		| |     | |  | \ | |  | | | | | | | \ | |     | | | | | | / |  | \ | |  | \ | |  / / 
	#		| |---- | |__| | | |__| | | | | | | | | |---- | | | | | | | |  | | | |__| | | |-< <  
	#		|_|     |_|  \_\ |_|  |_| |_| |_| |_| |_|____ |_|_|_|_|_/ \_|__|_/ |_|  \_\ |_|  \_\ 
																							 
	#	Copyright (C) 2025 Jan Maurice Dahlmanns [Bugfish]

	#	This program is free software; you can redistribute it and/or
	#	modify it under the terms of the GNU Lesser General Public License
	#	as published by the Free Software Foundation; either version 2.1
	#	of the License, or (at your option) any later version.

	#	This program is distributed in the hope that it will be useful,
	#	but WITHOUT ANY WARRANTY; without even the implied warranty of
	#	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	#	GNU Lesser General Public License for more details.

	#	You should have received a copy of the GNU Lesser General Public License
	#	along with this program; if not, see <https://www.gnu.org/licenses/>.
	
	########################################################################
	// Search Function with Scoring
	########################################################################
	function x_search($mysql, $table, $search_fields = [], $get_fields = [], $search_string = "", $uniqueref = "id") {
		// Abort if Search String is empty or invalid
		if (empty($search_string) || trim($search_string) === "") {
			return false;
		}

		$search_string = trim($search_string);
		// Split search string on spaces, normalize multiple spaces
		if (strpos($search_string, " ") !== false) {
			$search_string = preg_replace('/\s+/', ' ', $search_string);
			$search_terms = explode(" ", $search_string);
		} else {
			$search_terms = [$search_string];
		}

		if (empty($search_fields)) {
			// Default search fields with weights
			$search_fields = [
				["title", 3],
				["text", 1],
				["category", 2],
				["sec_category", 2],
			];
		}

		// Prepare query parts
		$where_clauses = [];
		$bindings = [];

		foreach ($search_terms as $term) {
			$term_clauses = [];
			foreach ($search_fields as $field_weight) {
				$field = $field_weight[0];
				$term_clauses[] = "`$field` LIKE ?";
				$bindings[] = "%$term%";
			}
			$where_clauses[] = "(" . implode(" OR ", $term_clauses) . ")";
		}

		$where_sql = implode(" AND ", $where_clauses);

		// Select fields to get, at least include uniqueref
		if (empty($get_fields)) {
			$get_fields = array_map(fn($f) => $f[0], $search_fields);
		}
		if (!in_array($uniqueref, $get_fields, true)) {
			array_unshift($get_fields, $uniqueref);
		}

		$fields_sql = implode(", ", array_map(fn($f) => "`$f`", $get_fields));

		$query = "SELECT $fields_sql FROM `$table` WHERE $where_sql";

		$results = $mysql->select($query, true, array_map(fn($b) => ["type" => "s", "value" => $b], $bindings));
		if (!$results) {
			return [];
		}

		// Score calculation per result row
		$scored_results = [];
		foreach ($results as $row) {
			$score = 0;
			foreach ($search_terms as $term) {
				$termLower = mb_strtolower($term);
				foreach ($search_fields as $field_weight) {
					$field = $field_weight[0];
					$weight = $field_weight[1];
					$fieldValue = mb_strtolower($row[$field] ?? "");
					$count = substr_count($fieldValue, $termLower);
					$score += $count * $weight;
				}
			}
			$row['score'] = $score;
			$scored_results[] = $row;
		}

		// Sort results by score descending
		usort($scored_results, fn($a, $b) => $b['score'] <=> $a['score']);

		return $scored_results;
	}
