<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use \atoum;

class DBmysql extends atoum {

   private $olddb;

   public function beforeTestMethod($method) {
      $this->olddb = new \DB();
      $this->olddb->dbdefault = 'glpitest0723';
      $this->olddb->connect();
      $this->boolean($this->olddb->connected)->isTrue();
   }

   public function afterTestMethod($method) {
      $this->olddb->close();
   }

   /**
    * Test updated database against fresh install
    *
    * @return void
    */
   public function testUpdatedDatabase() {
      global $DB;

      $fresh_tables = $DB->list_tables();
      foreach ($fresh_tables as $fresh_table) {
         $table = $fresh_table['TABLE_NAME'];
         $this->boolean($this->olddb->tableExists($table, false))->isTrue("Table $table does not exists from migration!");

         $create = $DB->getTableSchema($table);
         $fresh = $create['schema'];
         $fresh_idx = $create['index'];

         $update = $this->olddb->getTableSchema($table);
         $updated = $update['schema'];
         $updated_idx = $update['index'];

         //compare table schema
         $this->string($updated)->isIdenticalTo($fresh);
         //check index
         $fresh_diff = array_diff($fresh_idx, $updated_idx);
         $this->array($fresh_diff)->isEmpty("Index missing in update for $table: " . implode(', ', $fresh_diff));
         $update_diff = array_diff($updated_idx, $fresh_idx);
         $this->array($update_diff)->isEmpty("Index missing in empty for $table: " . implode(', ', $update_diff));
      }
   }
}
