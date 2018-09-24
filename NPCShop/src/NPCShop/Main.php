<?php

namespace NPCShop;

use NPCShop\entity\MarketNPC;

use NPCShop\event\SpawnNPCEvent;
use NPCShop\event\DeleteNPCEvent;
use NPCShop\event\OpenShopEvent;
use NPCShop\event\ShoppingEvent;

use onebone\economyapi\EconomyAPI;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\utils\Config;
use pocketmine\scheduler\PluginTask;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\entity\Human;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\event\server\DataPacketReceiveEvent;

class Main extends PluginBase implements Listener{
	
	public $prefix;
	public function onEnable(){
		
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		
		$this->getServer()->getPluginManager()->registerEvents(new SpawnNPCEvent($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new DeleteNPCEvent($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new OpenShopEvent($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new ShoppingEvent($this), $this);
		
	    Entity::registerEntity(MarketNPC::class, true);
		
	    $this->prefix = "§l§b[ §fNPCShop §b] §7";
		
	    @mkdir($this->getDataFolder());
	    $this->DataBase = new Config($this->getDataFolder() . "DataBase.yml", Config::YAML);
	    $this->db = $this->DataBase->getAll();
	    $this->PlayerData= new Config($this->getDataFolder() . "PlayerData.yml", Config::YAML);
	    $this->pd = $this->PlayerData->getAll();
    }
	
	public function onJoin(PlayerJoinEvent $event){
		$name = $event->getPlayer()->getName();
		$this->pd ["모드"] [$name] = "노모드";
		$this->onSave();
	}
	
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args):bool{
		if($command->getName() == "상점"){
			if($sender instanceof Player){
				$inv = $sender->getInventory();
				$arinv = $sender->getArmorInventory();
				$name = $sender->getName();
				if($sender->isOp()){
					if(!isset($args[0])){
						$sender->sendMessage("§l§f===== [ 상점 명령어 ] =====");
						$sender->sendMessage($this->prefix . "/상점 생성 - 상점을 생성합니다.");
						$sender->sendMessage($this->prefix . "/상점 제거 - 상점을 제거합니다.");
						$sender->sendMessage($this->prefix . "/상점 작업취소 - 진행중인 작업을 취소합니다.");
						return true;
					} else{
						switch($args[0]){
							case "생성":
							    $this->sendCreateUI($sender);
							    return true;
								break;
								
							case "제거":
								if($this->pd ["모드"] [$name] == "노모드"){
									$this->pd ["모드"] [$name] = "제거모드";
									$this->onSave();
									$sender->sendMessage($this->prefix . "제거하고 싶은 NPC를 터치해 주세요.");
									$sender->sendMessage($this->prefix . "/상점 작업취소 명령어를 사용하여 작업을 취소할 수 있습니다.");
									return true;
								} else{
									$sender->sendMessage($this->prefix . "진행중인 작업이 있어 진행할 수 없습니다.");
									return true;
								}
								break;
								
							case "작업취소":
								if($this->pd ["모드"] [$name] == "노모드"){
									$sender->sendMessage($this->prefix . "진행중인 작업이 없어 진행할 수 없습니다.");
									return true;
								} else{
									$this->pd ["모드"] [$name] = "노모드";
									$this->onSave();
									$sender->sendMessage($this->prefix . "진행중인 작업을 취소 했습니다.");
									return true;
								}
								break;
						}
						return true;
					}
					return true;
				} else{
					$sender->sendMessage($this->prefix . "권한이 부족합니다");
					return true;
				}
				return true;
			} else{
				$sender->sendMessage($this->prefix . "인게임에서 사용해 주세요.");
				return true;
			}
		}
	}
	
	public function sendCreateUI($player){
		$packet = new ModalFormRequestPacket();
		$packet->formId = 4343;
		$packet->formData = json_encode([
		    "type" => "custom_form",
		    "title" => "{$this->prefix}",
		    "content" => [
		        [
		        "type" => "input",
		        "text" => "§l§0NPC의 이름을 적어주세요."
		        ],
		        [
		        "type" => "input",
		        "text" => "§l§0아이템의 구매가를 적어주세요."
		        ],
		        [
		        "type" => "input",
		        "text" => "§l§0아이템의 판매가를 적어주세요."
		        ],
		    ]
		]);
		$player->dataPacket($packet);
	}
	
	public function sendShopUI($player, $arr){
		$arr = explode(":", $arr);
		$packet = new ModalFormRequestPacket();
		$packet->formId = 4344;
		$packet->formData = json_encode([
		    "type" => "custom_form",
		    "title" => "{$this->prefix}",
		    "content" => [
		        [
		        "type" => "label",
		        "text" => "{$arr[0]}\n§l§a구매가 : §b{$arr[1]}\n§l§a판매가 : §b{$arr[2]}"
		        ],
		        [
		        "type" => "dropdown",
		        "text" => "§l§0어떤 것을 도와드릴까요?",
		        "options" => ["구매", "판매"]
		        ],
		        [
		        "type" => "input",
		        "text" => "§l§0갯수를 적어주세요."
		        ],
		    ]
		]);
		$player->dataPacket($packet);
	}
	
	public function onSave(){
		$this->DataBase->setAll($this->db);
		$this->DataBase->save();
		$this->PlayerData->setAll($this->pd);
		$this->PlayerData->save();
	}
}