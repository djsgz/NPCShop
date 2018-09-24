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

use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\event\server\DataPacketReceiveEvent;

use pocketmine\math\Vector3;

class SpawnNPCEvent implements Listener{
	private $owner;
	public function __construct(Main $owner){
		$this->owner = $owner;
	}
	
	public function CreateShop(DataPacketReceiveEvent $event){
		$player = $event->getPlayer();
		$packet = $event->getPacket();
		if($packet instanceof ModalFormResponsePacket and $packet->formId == 4343){
			$data = json_decode($packet->formData, true);
			if($data[0] == null or $data[1] == null or $data[2] == null){
				$player->sendMessage("{$this->owner->prefix}모든 정보를 정확히 입력해주세요.");
			}else{
				$nbt = new CompoundTag("", [
						new ListTag("Pos", [
						new DoubleTag("", $player->x),
						new DoubleTag("", $player->y),
						new DoubleTag("", $player->z) ]),
						new ListTag("Motion", [
						new DoubleTag("", 0),
						new DoubleTag("", 0),
						new DoubleTag("", 0) ]),
						new ListTag("Rotation",[
						new FloatTag(0, $player->getYaw()),
						new FloatTag(0, $player->getPitch())]),
						new CompoundTag("Skin", [
								"Data" => new StringTag("Data", $player->getSkin()->getSkinData()),
								"Name" => new StringTag("Name", $player->getSkin()->getSkinId()),
							]),
						]);
						$entity = Entity::createEntity("MarketNPC", $player->getLevel(), $nbt);
						$buyM = $this->getBuyM((int)$data[1]);
						$sellM = $this->getSellM((int)$data[2]);
						$entity->setNameTag("{$data[0]}\n§l§a구매가 : §b{$buyM}\n§l§a판매가 : §b{$sellM}");
						$entity->setMaxHealth(1);
						$entity->setHealth(1);
						$inv = $player->getInventory();
						$einv = $entity->getInventory();
						$einv->setItemInHand($inv->getItemInHand());
						$entity->setNameTagVisible(true);
						$entity->setNameTagAlwaysVisible(true);
						$entity->spawnToAll();
						$player->sendMessage($this->owner->prefix . "상점 제작을 성공했습니다.");
						$this->owner->db[$player->getLevel()->getFolderName()]["{$player->x}:{$player->y}:{$player->z}"] = "{$data[0]}:{$buyM}:{$sellM}";
						$this->owner->onSave();
			}
		}
	}
	
	public function getBuyM(int $money){
		if($money >= 0){
			return $money;
		}else{
			return "구매불가";
		}
	}
	
	public function getSellM(int $money){
		if($money >= 0){
			return $money;
		}else{
			return "판매불가";
		}
	}
}