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

use \DbTestCase;

/* Test for inc/tickettask.class.php */

class TicketTask extends DbTestCase {

   /**
    * Create a new ticket and return its id
    *
    * @return integer
    */
   private function getNewTicket() {
      //create reference ticket
      $ticket = new \Ticket();
      $this->integer((int)$ticket->add([
            'name'         => 'ticket title',
            'description'  => 'a description',
            'content'      => ''
      ]))->isGreaterThan(0);

      $this->boolean($ticket->isNewItem())->isFalse();
      return (int)$ticket->getID();
   }

   public function testGetTaskList() {

      $this->login();
      $ticketId = $this->getNewTicket();
      $uid = getItemByTypeName('User', TU_USER, true);

      $tasksstates = [
         \Planning::TODO,
         \Planning::TODO,
         \Planning::INFO
      ];
      //create few tasks
      $task = new \TicketTask();
      foreach ($tasksstates as $taskstate) {
         $this->integer(
            $task->add([
               'state'        => $taskstate,
               'tickets_id'   => $ticketId,
               'users_id_tech'=> $uid
            ])
         )->isGreaterThan(0);
      }

      $iterator = $task::getTaskList('todo', false);
      $this->string($iterator->getSql())->isIdenticalTo(
         'SELECT `glpi_tickettasks`.`id` FROM `glpi_tickettasks` INNER JOIN `glpi_tickets` ON (`glpi_tickettasks`.`tickets_id` = `glpi_tickets`.`id`) WHERE `glpi_tickets`.`status` IN (1, 2, 3, 4) AND `glpi_tickettasks`.`state` = 1 AND `glpi_tickettasks`.`users_id_tech` = ' . $uid . ' ORDER BY `glpi_tickettasks`.`date_mod` DESC'
      );
      //we create two ones plus the one in bootstrap data
      $this->integer(count($iterator))->isIdenticalTo(3);

      $iterator = $task::getTaskList('todo', true);
      $this->boolean($iterator)->isFalse();

      $_SESSION['glpigroups'] = [42, 157];
      $iterator = $task::getTaskList('todo', true);
      $this->string($iterator->getSql())->isIdenticalTo(
         'SELECT `glpi_tickettasks`.`id` FROM `glpi_tickettasks` INNER JOIN `glpi_tickets` ON (`glpi_tickettasks`.`tickets_id` = `glpi_tickets`.`id`) WHERE `glpi_tickets`.`status` IN (1, 2, 3, 4) AND `glpi_tickettasks`.`state` = 1 AND `glpi_tickettasks`.`groups_id_tech` IN (42, 157) ORDER BY `glpi_tickettasks`.`date_mod` DESC'
      );
      //no task for those groups
      $this->integer(count($iterator))->isIdenticalTo(0);
   }

   public function testCentralTaskList() {
      $this->login();
      $ticketId = $this->getNewTicket();
      $uid = getItemByTypeName('User', TU_USER, true);

      $tasksstates = [
         \Planning::TODO,
         \Planning::TODO,
         \Planning::TODO,
         \Planning::INFO,
         \Planning::INFO
      ];
      //create few tasks
      $task = new \TicketTask();
      foreach ($tasksstates as $taskstate) {
         $this->integer(
            $task->add([
               'state'        => $taskstate,
               'tickets_id'   => $ticketId,
               'users_id_tech'=> $uid
            ])
         )->isGreaterThan(0);
      }

      //How could we test there are 4 matching links?
      $this->output(
         function () {
            \TicketTask::showCentralList(0, 'todo', false);
         }
      )
         ->contains("Ticket tasks to do <span class='primary-bg primary-fg count'>4</span>")
         ->matches("/a id='[^']+' href='\/glpi\/front\/ticket.form.php\?id=\d+[^']+'>/");

      //How could we test there are 2 matching links?
      $this->output(
         function () {
            $_SESSION['glpidisplay_count_on_home'] = 2;
            \TicketTask::showCentralList(0, 'todo', false);
            unset($_SESSION['glpidisplay_count_on_home']);
         }
      )
         ->contains("Ticket tasks to do <span class='primary-bg primary-fg count'>2 on 4</span>")
         ->matches("/a id='[^']+' href='\/glpi\/front\/ticket.form.php\?id=\d+[^']+'>/");
   }
}
