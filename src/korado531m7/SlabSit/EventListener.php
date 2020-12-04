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

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;

class EventListener implements Listener{
    /** @var SlabSit */
    private $instance;
    
    public function __construct(SlabSit $instance){
        $this->instance = $instance;
    }

    public function onBreak(BlockBreakEvent $event){
        if($this->instance->standWhenBreak()){
            $this->instance->removeSeatDataByPosition($event->getBlock());
        }
    }
    
    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if($this->instance->canSit($player, $block) && $this->instance->checkClick($event)){
            $targetSeat = $this->instance->getSeatDataByPosition($block);
            if($targetSeat instanceof SeatData){
                $player->sendMessage(str_replace(['@p','@b'], [$targetSeat->getPlayer()->getName(), $block->getName()], $this->instance->getConfig()->get('try-to-sit-already-inuse', 'This seat is used by @p')));
            }else{
                $seatData = new SeatData($player, $block);
                $seatData->seat($this->instance->getServer()->getOnlinePlayers());
                $this->instance->addSeatData($seatData);
                $player->sendTip(str_replace(['@b', '@x', '@y', '@z'], [$block->getName(), $block->getFloorX(), $block->getFloorY(), $block->getFloorZ()], $this->instance->getConfig()->get('send-tip-when-sit', 'Tap jump to exit the seat')));
            }
        }
    }
    
    public function onJoin(PlayerJoinEvent $event){
        if(count($this->instance->getAllSeatData()) >= 1){
            $target = $event->getPlayer();
            $this->instance->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use ($target) : void{
                foreach($this->instance->getAllSeatData() as $seatDatum){
                    $seatDatum->seat([$target]);
                }
            }), 30);
        }
    }

    public function onMove(PlayerMoveEvent $event) : void{
        $seatData = $this->instance->getSeatDataByPlayer($event->getPlayer());
        if($seatData instanceof SeatData){
            $seatData->optimizeRotation();
        }
    }

    public function onDamage(EntityDamageEvent $event) : void{
        $sacrifice = $event->getEntity();
        if($sacrifice instanceof Player){
            if($this->instance->isSlabSitting($sacrifice) && $this->instance->isDisabledDamagesWhenSit()){
                $event->setCancelled();
            }
        }
    }

    public function onLeave(DataPacketReceiveEvent $event){
        $packet = $event->getPacket();
        if($packet instanceof InteractPacket && $packet->action === InteractPacket::ACTION_LEAVE_VEHICLE){
            $seatData = $this->instance->getSeatDataByPlayer($event->getPlayer());
            if($seatData instanceof SeatData){
                $this->instance->removeSeatDataByPosition($seatData->getBlock());
            }
        }
    }

    public function onQuit(PlayerQuitEvent $event){
        $seatData = $this->instance->getSeatDataByPlayer($event->getPlayer());
        if($seatData instanceof SeatData){
            $this->instance->removeSeatDataByPosition($seatData->getBlock());
        }
    }
}