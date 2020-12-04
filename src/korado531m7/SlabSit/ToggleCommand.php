<?php
/*
 * This file is part of SlabSit.
 *
 *  SlabSit is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  SlabSit is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with SlabSit.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace korado531m7\SlabSit;


use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class ToggleCommand extends PluginCommand{
    public function __construct(Plugin $owner){
        /** @var SlabSit $owner */
        parent::__construct($owner->getToggleCommandLabel(), $owner);
        $this->setPermission('slabsit.toggle');
        $this->setDescription('Toggle to sit on the slabs');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$this->testPermission($sender)){
            return;
        }

        if($sender instanceof Player){
            $sender->sendMessage($this->toggle(strtolower($sender->getName())) ? (TextFormat::GREEN . 'You will be able to sit on the slabs') : (TextFormat::RED . 'You will not be able to sit on the slabs'));
        }else{
            $this->getPlugin()->getLogger()->info('You can use this command in-game');
        }
    }

    private function toggle(string $name) : bool{
        /** @var SlabSit $owner */
        $owner = $this->getPlugin();
        $conf = $owner->getToggleConfig();
        $next = ($conf->exists($name)) ? !$conf->get($name) : false;
        $conf->set($name, $next);
        $conf->save();
        return $next;
    }
}