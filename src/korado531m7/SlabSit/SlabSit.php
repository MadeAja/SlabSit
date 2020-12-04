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

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\block\Slab;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class SlabSit extends PluginBase{
    const CONFIG_VERSION = 4;

    /** @var SeatData[] */
    private $seatData = [];
    /** @var Config */
    private $toggleConfig;
    
    public function onEnable(){
        $this->toggleConfig = new Config($this->getDataFolder() . 'toggle.yml', Config::YAML);
        $this->reloadConfig();
        if(!$this->isCompatibleWithConfig()){
            $this->getLogger()->warning('Your configuration file is outdated. To update the config, please delete it at '.($this->getDataFolder() . 'config.yml'));
        }
        if($this->getConfig()->get('register-sit-command', true)){
            $this->getServer()->getCommandMap()->register($this->getName(), new ToggleCommand($this));
        }
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    public function isAllowedWhileSneaking() : bool{
        return (bool) $this->getConfig()->get('allow-seat-while-sneaking', true);
    }

    public function standWhenBreak() : bool{
        return (bool) $this->getConfig()->get('stand-up-when-break-block', true);
    }

    public function isToggleEnabled(Player $player) : bool{
        return (bool) $this->toggleConfig->get(strtolower($player->getName()));
    }

    public function canApplyWorld(Level $level) : bool{
        return ((bool) $this->getConfig()->get('apply-all-worlds', true)) ? true : (in_array($level->getFolderName(), array_map('trim', explode(',', (string) $this->getConfig()->get('apply-world-names', '')))));
    }

    public function isDisabledDamagesWhenSit() : bool{
        return (bool) $this->getConfig()->get('disable-damage-when-sit', false);
    }

    public function isAllowedOnlyRightClick() : bool{
        return (bool) $this->getConfig()->get('allow-only-right-click', false);
    }

    public function isDefaultToggleEnabled() : bool{
        return (bool) $this->getConfig()->get('default-toggle-sit', true);
    }

    public function checkClick(PlayerInteractEvent $event) : bool{
        return $this->isAllowedOnlyRightClick() ? ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) : ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK || $event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK);
    }

    public function getToggleCommandLabel() : string{
        return (string) $this->getConfig()->get('toggle-command-label', 'sit');
    }

    public function canSit(Player $player, Block $block) : bool{
        return (
            ($this->isDefaultToggleEnabled() || $this->isToggleEnabled($player)) &&
            $this->canApplyWorld($block->getLevel()) &&
            $block instanceof Slab &&
            !$this->isSlabSitting($player) &&
            ($this->isAllowedWhileSneaking() || (!$this->isAllowedWhileSneaking() && !$player->isSneaking()))
        );
    }

    public function addSeatData(SeatData $data) : void{
        $this->seatData[] = $data;
    }

    public function getSeatDataByPlayer(Player $player) : ?SeatData{
        foreach($this->seatData as $seatDatum)
            if($player->getId() === $seatDatum->getPlayer()->getId())
                return $seatDatum;
        return null;
    }

    public function getSeatDataByPosition(Position $pos) : ?SeatData{
        foreach($this->seatData as $seatDatum)
            if($seatDatum->equals($pos))
                return $seatDatum;
        return null;
    }

    public function removeSeatDataByPosition(Position $pos) : bool{
        foreach($this->seatData as $key => $seatDatum)
            if($seatDatum->equals($pos)){
                $seatDatum->stand();
                unset($this->seatData[$key]);
                return true;
            }
        return false;
    }

    /**
     * Return player is sitting on the slabs.
     * Developers have to use this method to check whether player is sitting
     *
     * @param Player $player
     *
     * @return bool
     */
    public function isSlabSitting(Player $player) : bool{
        return $this->getSeatDataByPlayer($player) instanceof SeatData;
    }

    /**
     * @return SeatData[]
     */
    public function getAllSeatData() : array{
        return $this->seatData;
    }

    public function getToggleConfig() : Config{
        return $this->toggleConfig;
    }

    private function isCompatibleWithConfig() : bool{
        return $this->getConfig()->get('config-version') == self::CONFIG_VERSION;
    }
}
