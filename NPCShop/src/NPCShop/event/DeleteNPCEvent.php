<?php

namespace NPCShop\event;

use NPCShop\Main;
use NPCShop\entity\MarketNPC;

use pocketmine\event\Listener;

use pocketmine\utils\Config;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;

use pocketmine\Player;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;

use pocketmine\item\Item;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use pocketmine\math\Vector3;

class DeleteNPCEvent implements Listener{
	private $owner;
	public function __construct(Main $owner){
		$this->owner = $owner;
	}
		
	public function TouchNPC(EntityDamageEvent $event){
		$npc = $event->getEntity();
		if($npc instanceof MarketNPC){
			$event->setCancelled();
			$npc->setMotion(new Vector3(0, 0, 0));
			if($event instanceof EntityDamageByEntityEvent){
				$player = $event->getDamager();
				    if($player instanceof Player){
				    if($this->owner->pd["모드"][$player->getName()] == "제거모드"){
				       unset($this->owner->db[$npc->getLevel()->getFolderName()]["{$npc->x}:{$npc->y}:{$npc->z}"]);
				       $this->owner->pd["모드"][$player->getName()] = "노모드";
				       $this->owner->onSave();
			           $npc->getInventory()->clearAll();
			           $npc->kill();
				       $player->sendMessage("{$this->owner->prefix}상점을 제거했습니다.");
				    }
				}
			}
		}
	}
}